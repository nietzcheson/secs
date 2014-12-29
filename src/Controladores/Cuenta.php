<?php
namespace Controladores;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\FormServiceProvider;
use Formularios;

class Cuenta implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = $app['controllers_factory'];

    $controllers->match('/', function (Request $request) use ($app) {


      $app->register(new Formularios\CuentaForm());
      $form = $app['cuentaForm']();

        $_mensaje = "";
        $_error = "";

      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){

          $data = $form->getData();

          $sql = array(
            "id_u_marca" => $data["id_u_marca"],
            "nombre_marca" => $data["nombre_marca"],
            "email" => $data["email"],
            "telefono1" => $data["telefono1"],
            "web" => $data["web"],
            "facebook" => $data["facebook"],
            "twitter" => $data["twitter"],
            "observaciones" => $data["observaciones"]
          );

          $sql = $app["sql"]("update","marcas",$sql);

          if($sql == true){
            $_mensaje = "Los datos se han actualizado...";
          }else{
            $_error = "No hemos podido almacenar los datos. Intenta nuevamente...";
          }

      }
    }

    return $app['twig']->render('views/admin-cuenta.html', array(
        'form' => $form->createView(),
        'titulo' => 'Cuenta',
        'subtitulo' => array(
          'titulo' => 'Administración de cuenta',
          'color_box' => '#7266ba'
        ),
      "_mensaje"=> $_mensaje,
      "_error" => $_error,
    ));

    })
    ->bind('admin_cuenta')
    ;

    $controllers->match('/usuarios', function (Request $request) use ($app) {

      $usuarios = $app["db"]->fetchAll("SELECT p.id_prospecto,p.nombre_prospecto, p.apellido_prospecto, p.rol_prospecto
        FROM marcas_clientes mc LEFT JOIN prospectos p ON mc.id_u_cliente = p.id_u_prospecto
        WHERE mc.id_u_marca='{$app["id_marca"]}'
      ");
      $token = $app["token"];

      $prospecto = $app['db']->fetchAssoc('SELECT id_u_prospecto FROM prospectos WHERE email_prospecto = ? ',array(strtolower($token->getUsername())));

      return $app['twig']->render('views/usuarios.html',
      array(
        "titulo" => "Cuenta",
        "usuarios" => $usuarios,
        "id_prospecto" => $prospecto["id_u_prospecto"],
        'subtitulo' => array(
          'titulo' => 'Usuarios',
          'color_box' => '#f05050'
        ),
      ));
    })
    ->bind('cuenta_usuarios')
    ;

    $controllers->match('crear-usuario', function (Request $request) use ($app) {

      $app->register(new Formularios\UsuariosForm());
      $form = $app['usuariosForm']();

      $_mensaje = "";
      $_error = "";
      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){
          $data = $form->getData();

          $_error = "El password no es igual repítalo nuevamente";

          $_error = "Este email no se puede usar porque ya está registrado con otra cuenta. Para poder continuar el registro debe usar un nuevo email...";
          $emails = $app["db"]->fetchAssoc("SELECT * FROM prospectos WHERE email_prospecto='{$data["email_prospecto"]}' ");

          if(!$emails){
            if($data["secs_pass"]===$data["re_pass"]){


              // $id_prospecto = $app["db"]->fetchAssoc("SELECT * FROM prospectos ORDER BY id_prospecto DESC");
              //
              // $id_u = $id_prospecto["id_prospecto"] +1 ;
              // $data["id_u_prospecto"] = $id_u;


              $user = $app["token"];

              $encoder = $app['security.encoder_factory']->getEncoder($user);
              $data["secs_pass"] = $encoder->encodePassword($data["secs_pass"], $user->getSalt());

              unset($data['re_pass']);

              $data["id_u_prospecto"] = "";

              $sql = $app["sql"]("insert","prospectos",$data);

              $id_prospecto = $app["db"]->fetchAssoc("SELECT * FROM prospectos ORDER BY id_prospecto DESC");

              $updateID = array(
                'id_prospecto' => $id_prospecto['id_prospecto'],
                'id_u_prospecto' => $id_prospecto['id_prospecto']
              );

              $sql = $app["sql"]("update","prospectos",$updateID);

              $datos = array(
                "id_u_marca" => $app["id_marca"],
                "id_u_cliente" => $id_prospecto["id_prospecto"],
                "fecha_creacion" => "Data"
              );

              $sql = $app["sql"]("insert","marcas_clientes",$datos);

              if($sql === true){
                return $app->redirect($app['url_generator']->generate('cuenta_usuarios'));
              }else{
                $_error = "No hemos podido almacenar los datos. Intenta nuevamente...";
              }
            }
          }
        }
      }

      return $app['twig']->render('views/crear-usuario.html', array(
      "form"=>$form->createView(),
      "titulo" => "Cuenta",
      'subtitulo' => array(
        'titulo' => 'Crear usuario',
        'color_box' => '#f05050'
      ),
      '_error' => $_error,
      ));


    })->bind('crear_usuarios');


    $controllers->match('usuario/{id}', function (Request $request, $id) use ($app) {

      $app->register(new Formularios\UsuariosForm());


      $usuario = $app["db"]->fetchAssoc("SELECT * FROM prospectos WHERE id_prospecto= '{$id}'");

      $form = $app['usuariosForm']($usuario);

      $_mensaje = "";
      $_error = "";
      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){
          $data = $form->getData();


          $app["comparandoPass"] = function() use($data){

              if($data["secs_pass"] === $data["re_pass"]){
                return true;
              }else{
                return false;
              }

          };

          $app["actualizandoUsuario"] = function() use($app, $data){

            $user = $app["token"];
            $encoder = $app['security.encoder_factory']->getEncoder($user);
            $data["secs_pass"] = $encoder->encodePassword($data["secs_pass"], $user->getSalt());
            unset($data['re_pass']);

            $sql = $app["sql"]("update","prospectos",$data);
            return "Los datos se han actualizado";
          };


          $emailUsuario = $app["db"]->fetchAssoc("SELECT * FROM prospectos WHERE email_prospecto='{$data["email_prospecto"]}' AND id_prospecto = '{$data["id_prospecto"]}'");


          //Se compara si los datos del id_prospecto con email_prospecto hacen parte de la misma fila
          if(!$emailUsuario){

            //Se busca saber si el email exite dentro de la tabla en la base de datos
            $emails = $app["db"]->fetchAssoc("SELECT * FROM prospectos WHERE email_prospecto='{$data["email_prospecto"]}' ");

            if($emails){
              $_error = "Este email no se puede usar porque ya está registrado con otra cuenta. Para poder continuar el registro debe usar un nuevo email...";
            }else{
              if($app["comparandoPass"]==true){
                $_mensaje = $app["actualizandoUsuario"];
              }else{
                $_error = "Los password no son iguales";
              }
            }

          }else{

            if($app["comparandoPass"]==true){
              $_mensaje = $app["actualizandoUsuario"];
            }else{
              $_error = "Los password no son iguales";
            }

          }

        }
      }

      return $app['twig']->render('views/usuario.html', array(
      "form"=>$form->createView(),
      "titulo" => "Cuenta",
      'subtitulo' => array(
      'titulo' => 'Usuario | '. $usuario["nombre_prospecto"]. " ". $usuario["apellido_prospecto"],
      'color_box' => '#f05050'
      ),
      '_error' => $_error,
      '_mensaje' => $_mensaje
      ));


    })->bind('cuenta_usuario');

    $controllers->get("eliminar-usuario/{id}", function($id) use($app){

      $marcas_clientes = $app["db"]->executeQuery("DELETE FROM marcas_clientes WHERE id_u_cliente = $id");
      $prospectos = $app["db"]->executeQuery("DELETE FROM prospectos WHERE id_prospecto = $id");

      if($marcas_clientes && $prospectos){
        return $app->redirect($app['url_generator']->generate('cuenta_usuarios'));
      }

    })->bind('cuenta_eliminar_usuario');

    $controllers->match("razones-sociales", function() use($app){

      $razones = $app["db"]->fetchAll("SELECT *
        FROM marca_razon_social mrs LEFT JOIN razones_sociales rs
        ON mrs.id_u_rs = rs.id_u_rs
        WHERE mrs.id_u_marca='{$app["id_marca"]}'
      ");

      return $app['twig']->render('views/razones-sociales.html', array(
        "titulo" => "Cuenta",
        "razones_sociales" => $razones,
        'subtitulo' => array(
          'titulo' => 'Razones sociales',
          'color_box' => '#23b7e5'
        ),

      ));

    })->bind("cuenta_razones_sociales");

    $controllers->match("crear-razon-social", function(Request $request) use($app) {
      $app->register(new Formularios\RazonSocialForm());
      $form = $app['razonSocialForm']();

      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){
          $data = $form->getData();
          $data["id_u_rs"] = "";
          $data["id_u_prospecto"] = "";
          $sql = $app["sql"]("insert","razones_sociales",$data);

          $id_razon = $app["db"]->fetchAssoc("SELECT id_razon_s FROM razones_sociales ORDER BY id_razon_s DESC");

          $updateID = array(
            'id_razon_s' => $id_razon['id_razon_s'],
            'id_u_rs' => $id_razon['id_razon_s']
          );

          $sql = $app["sql"]("update","razones_sociales",$updateID);

          $datos = array(
            "id_u_marca" => $app["id_marca"],
            "id_u_rs" => $id_razon['id_razon_s'],
            "fecha_creacion" => date('d/m/Y')
          );

          $sql = $app["sql"]("insert","marca_razon_social",$datos);

          if($sql === true){
            return $app->redirect($app['url_generator']->generate('cuenta_razones_sociales'));
          }else{
            $_error = "No hemos podido almacenar los datos. Intenta nuevamente...";
          }

        }
      }


      return $app["twig"]->render("views/crear-razon-social.html", array(
        "titulo" => "Cuenta",
        "form" => $form->createView(),
        'subtitulo' => array(
          'titulo' => 'Crear razón social',
          'color_box' => '#23b7e5'
        ),
      ));

    })->bind("cuenta_crear_razon_social");

    $controllers->match('razon-social/{id}', function (Request $request, $id) use ($app) {
      $app->register(new Formularios\RazonSocialForm());


      $razon_social = $app["db"]->fetchAssoc("SELECT *
        FROM razones_sociales rs LEFT JOIN marca_razon_social mrs
        ON rs.id_u_rs = mrs.id_u_rs
        WHERE rs.id_razon_s= '{$id}'
        AND mrs.id_u_marca = '{$app["id_marca"]}'
        ");



      if(empty($razon_social)){
        return $app->redirect($app['url_generator']->generate('cuenta_razones_sociales'));
      }


      $form = $app['razonSocialForm']($razon_social);


      $_mensaje = "";
      $_error = "";
      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){
          $data = $form->getData();
          unset($data["id_marcar_razon"]);
          unset($data["id_u_marca"]);
          $sql = $app["sql"]("update","razones_sociales",$data);

          $_mensaje  = "Los datos se han actualizado";
        }
      }

      return $app['twig']->render('views/razon-social.html', array(
        "form"=>$form->createView(),
        "titulo" => "Cuenta",
        'subtitulo' => array(
          'titulo' => 'Razón social | '.$razon_social["razon_social"],
          'color_box' => '#23b7e5'
        ),
        '_error' => $_error,
        '_mensaje' => $_mensaje
      ));

    })->bind('cuenta_razon_social');


    $controllers->get("eliminar-razon/{id}", function($id) use($app){

      $razon_social = $app["db"]->fetchAssoc("SELECT *
        FROM razones_sociales rs LEFT JOIN marca_razon_social mrs
        ON rs.id_u_rs = mrs.id_u_rs
        WHERE id_razon_s='{$id}'
        AND mrs.id_u_marca = '{$app["id_marca"]}'
        ");


      if(empty($razon_social)){
        return $app->redirect($app['url_generator']->generate('cuenta_razones_sociales'));
      }


      $marcas_clientes = $app["db"]->executeQuery("DELETE FROM razones_sociales WHERE id_razon_s = $id");
      $prospectos = $app["db"]->executeQuery("DELETE FROM marca_razon_social WHERE id_u_rs = '{$razon_social["id_u_rs"]}'");

      if($marcas_clientes && $prospectos){
        return $app->redirect($app['url_generator']->generate('cuenta_razones_sociales'));
      }

      return $id;

    })->bind('cuenta_eliminar_razon');

    return $controllers;
  }



}

?>
