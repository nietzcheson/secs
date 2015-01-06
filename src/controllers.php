<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Types\PerfilType;
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
  return $app['twig']->render('views/note.html', array());
})
->bind('permisos')
;





$app->get('/usuarios', function () use ($app) {
    return $app['twig']->render('views/note.html', array());
})
->bind('usuarios')
;

$app->get('/admin', function () use ($app) {

    // if (!$app['security']->isGranted('ROLE_ADMIN')) {
    //     return new Response("No puedes ingresar");
    //     exit();
    // }

    $token = $app['security']->getToken();
    if (null !== $token) {
      $user = $token->getUser();
    }

    return $app['twig']->render('views/index.html', array());
})
->bind('admin')
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
