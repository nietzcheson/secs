<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;

class PerfilForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['perfilForm'] = $app->protect(function () use ($app) {

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $token = $app["token"];

      $usuario = $app["db"]->fetchAssoc("SELECT * FROM prospectos WHERE email_prospecto=:email_prospecto", array(
        "email_prospecto" => $token->getUsername()
      ));

      $resultado = $app["db"]->fetchAll("SELECT * FROM paises");

      $paises = array();
      foreach($resultado as $pais){
        $paises[$pais['id_pais']] = $pais["nombre_pais"];
      }

      //$form = $app["form_perfil"]($usuario);
      $form = $app['form.factory']->createBuilder('form',$usuario)
      ->add('nombre_prospecto',null, array(
        'label'      => "Nombre",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El nombre ". $mensajes["vacio"])))
      ))
      ->add('apellido_prospecto', null, array(
        'label'      => "Apellido",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El apellido ". $mensajes["vacio"])))
      ))
      ->add('telefono_prospecto', null, array(
        'label'      => "Teléfono",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El teléfono ". $mensajes["vacio"])))
      ))
      ->add('email_prospecto', null, array(
        'label'      => "Email",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El email ". $mensajes["vacio"])))
      ))
      ->add('pais_prospecto', "choice", array(
        'label'      => "País",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El país ". $mensajes["vacio"]))),
        'choices' => $paises,
        "empty_value" => "Seleccione el país"
      ))
      ->add('estado_prospecto', null, array(
        'label'      => "Estado",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El Estado ". $mensajes["vacio"])))
      ))
      ->add('ciudad_prospecto', null, array(
        'label'      => "Ciudad",
        'constraints' => array(new Assert\NotBlank(array('message'=>"La ciudad ". $mensajes["vacio"])))
      ))
      ->add("Guardar","submit", array(
        'attr'=>array(
            'class'=>'btn btn-primary'
          )
      ))
      ->getForm();

      return $form;
    });

  }

  public function boot(Application $app)
  {
  }

}

?>
