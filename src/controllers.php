<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

$app->mount('/', new Controladores\Portada());
$app->mount('/perfil', new Controladores\Perfil());
$app->mount('/cuenta', new Controladores\Cuenta());
$app->mount('/proveedores', new Controladores\Proveedores());
$app->mount('/operaciones', new Controladores\Operaciones());
$app->mount('/ordenes', new Controladores\Ordenes());

$app->get('/permisos', function () use ($app) {

  return $app['twig']->render('views/permisos.html', array("clientes" => $app["clientes"]));

})
->bind('permisos')
;


$app->get('/permisos/cliente-{cliente}', function ($cliente) use ($app) {

  $usuarios_cliente = $app["db"]->fetchAll("SELECT *
    FROM marcas_clientes mc LEFT JOIN prospectos p
    ON mc.id_u_cliente = p.id_u_prospecto
    WHERE mc.id_u_marca = '{$cliente}'
    ");

    $valor = "Un valor";

    return $app['twig']->render('views/permisos.html', array("id_cliente"=>$cliente,"clientes" => $app["clientes"], "prospectos" => $usuarios_cliente));
  })
  ->bind('usuarios_cliente')
  ;


  $app->match('/permisos/cliente-{cliente}/prospecto-{prospecto}', function ($cliente,$prospecto, Request $request) use ($app) {

    $_prospecto = $app["db"]->fetchAssoc("SELECT * FROM prospectos WHERE id_u_prospecto='{$prospecto}'");

    $vacio = "Lleno";

    if(!$_prospecto["secs_pass"]){
      $vacio = "Vacío";
    }

    $form_pass = $app['form.factory']->createBuilder('form')
    ->add('generar', 'submit', array(
      'label' => $vacio
    ))
    ->getForm();


    $activo = false;

    if($_prospecto["activo"]!=0){
      $activo = true;
    }


    $pass = array(
      "activo" => $activo
    );

    $form = $app['form.factory']->createBuilder('form', $pass)
    ->add('activo', 'checkbox', array(
      'label' => 'Activo'
    ))
    ->add('guardar', 'submit', array(
      'label' => 'Guardar datos',
    ))
    ->getForm();

    $usuarios_cliente = $app["db"]->fetchAll("SELECT *
      FROM marcas_clientes mc LEFT JOIN prospectos p
      ON mc.id_u_cliente = p.id_u_prospecto
      WHERE mc.id_u_marca = '{$cliente}'
      ");

      if ('POST' == $request->getMethod()) {
        $form->bind($request);
        $form_pass->bind($request);

        if ($form_pass->isValid()) {
          $data = $form_pass->getData();

          $token = $app['security']->getToken();

          if (null !== $token) {
            $user = $token->getUser();
          }
          // encuentra el codificador adecuado para la instancia de UserInterface
          $encoder = $app['security.encoder_factory']->getEncoder($user);

          // codificar la contraseña "foo"
          $rand = rand(0,100000);

          $password = $encoder->encodePassword($rand, $user->getSalt());

          $app["db"]->ExecuteQuery("UPDATE prospectos SET secs_pass='{$password}' WHERE id_u_prospecto='{$prospecto}'");

          echo $rand;
          exit();

          $message = \Swift_Message::newInstance()
          ->setSubject('[YourSite] Feedback')
          ->setFrom(array('noreply@yoursite.com'))
          ->setTo(array('cristianangulonova@gmail.com'))
          ->setBody($request->get('message'));

          $app['mailer']->send($message);

          // redirect somewhere
          return $app->redirect($app['url_generator']->generate('prospecto', array('cliente'=>$cliente,"prospecto"=>$prospecto)));
        }

        if ($form->isValid()) {
          $data = $form->getData();

          if(empty($data["activo"])){
            $data["activo"] = 0;
          }

          $app["db"]->ExecuteQuery("UPDATE prospectos SET activo='{$data["activo"]}' WHERE id_u_prospecto='{$prospecto}'");

          return $app->redirect($app['url_generator']->generate('prospecto', array('cliente'=>$cliente,"prospecto"=>$prospecto)));

        }
      }

      return $app['twig']->render('views/permisos.html', array(
      "id_cliente"  =>$cliente,
      "clientes"    => $app["clientes"],
      "prospectos" => $usuarios_cliente,
      'form' => $form->createView(),
      'form_pass' => $form_pass->createView()
      ));
    })
    ->bind('prospecto')
    ;


$app->get('/usuarios', function () use ($app) {
    return $app['twig']->render('views/note.html', array());
})
->bind('usuarios')
;


$app->get('/usuarios/crear-usuario', function () use ($app) {

  if (!$app['security']->isGranted('ROLE_CREAR_USUARIOS')) {
      return $app->redirect('/');
      exit();
  }else{
    return new Response("Usuarios");
  }


})
->bind('crear-usuario')
;

$app->get('/login', function(Request $request) use ($app) {
    return $app['twig']->render('login.html', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/admin/logout',function(){})->bind("logout");

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html',
        'errors/'.substr($code, 0, 2).'x.html',
        'errors/'.substr($code, 0, 1).'xx.html',
        'errors/default.html',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
