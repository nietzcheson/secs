<?php
namespace Controladores;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\FormServiceProvider;
use Formularios;

class Perfil implements ControllerProviderInterface
{
  public function connect(Application $app)
  {
    $app->register(new FormServiceProvider());

    $controllers = $app['controllers_factory'];

    $controllers->match('/', function (Request $request) use ($app) {

      $app->register(new Formularios\PerfilForm());
      $form = $app['perfilForm']();

      $_mensaje = "";
      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){

          $app["sql"]("update","prospectos",$form->getData());
          $_mensaje = "Muy bien! Los datos se han actualizado";
        }
      }

      return $app['twig']->render('views/perfil.html', array(
      'form' => $form->createView(),
      "titulo" => "Perfil",
      "_mensaje"=>$_mensaje,
      'subtitulo' => array(
      'titulo' => 'Datos de perfil',
      'color_box' => '#fad733'
      ),
      ));
    })
    ->bind('perfil')
    ;

    $controllers->match('/pass', function (Request $request) use ($app) {

      $app->register(new Formularios\PassForm());
      $form = $app['passForm']();

        $_mensaje = "";
        $_error = "";
        if('POST' == $request->getMethod()){
          $form->bind($request);


          if($form->isValid()){
            $data = $form->getData();

            $encoder = $app['security.encoder_factory']->getEncoder($token);
            $pass = $encoder->encodePassword("foo", $token->getSalt());


            $token->getUsername();
            $pass_usuario = $app['db']->fetchAssoc('SELECT secs_pass FROM prospectos WHERE email_prospecto = ? ',array(strtolower($token->getUsername())));

            $pass_actual = $encoder->encodePassword($data["pass"], $token->getSalt());

            $flag_pass = 0;
            if($pass_usuario["secs_pass"] === $pass_actual){
              $flag_pass=1;
            }

            $n_pass = $encoder->encodePassword($data["n_pass"], $token->getSalt());
            $r_pass = $encoder->encodePassword($data["r_pass"], $token->getSalt());

            $flag_n_pass = 0;
            if($n_pass === $r_pass){
              $flag_n_pass = 1;
            }

            if($flag_pass == 1){

              if($flag_n_pass==1){

                $update = "UPDATE prospectos SET
                secs_pass         =    :secs_pass
                WHERE
                email_prospecto   =    :email_prospecto
                ";

                $resultado = $app['db']->prepare($update);
                $resultado->execute(
                  array(
                    "secs_pass"       => $n_pass,
                    "email_prospecto" => $token->getUsername()
                  )
                );

                $_mensaje = "La contraseña se ha actualizado";

              }else{
                $_error = "La nueva contraseña no son iguales...";
              }

            }else{
              $_error = "Revisa bien tu actual contraseña...";
            }
          }

        }

        return $app['twig']->render('views/pass.html', array(
        "form"=> $form->createView(),
        "titulo" => "Perfil",
        "_mensaje"=> $_mensaje,
        "_error" => $_error,
        'subtitulo' => array(
        'titulo' => 'Password',
        'color_box' => '#27c24c'
        ),
        )
        );
      })->bind("pass");


    return $controllers;
  }
}

?>
