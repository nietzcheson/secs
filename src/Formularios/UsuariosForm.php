<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;
class UsuariosForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['usuariosForm'] = $app->protect(function ($data = false) use ($app) {

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $resultado = $app["db"]->fetchAll("SELECT * FROM roles_secs");

      $roles = array();

      foreach($resultado as $role){
        $roles[$role['id']] = $role['role'] ." - ".$role['role_alias'];
      }

      $defaultRole = $data["rol_prospecto"];
      if(!$data){
        $data = array();
        $defaultRole = "";
      }


      $form = $app['form.factory']->createBuilder('form', $data)
      ->add('rol_prospecto','choice', array(
        'choices' => $roles,
        "empty_value" => "Seleccione el role",
        "data" => $defaultRole,
        "label" => "Role*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Tiene que escoger un tipo de role ")))
      ))
      ->add("nombre_prospecto","text", array(
        "label" => "Nombre*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El nombre ". $mensajes["vacio"])))
      ))
      ->add("apellido_prospecto","text", array(
        "label" => "Apellido",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El apellido ". $mensajes["obligatorio"])))
      ))
      ->add("telefono_prospecto","text", array(
        "label" => "Teléfono",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El teléfono ". $mensajes["vacio"])))
      ))
      ->add("email_prospecto","text", array(
        "label" => "Email",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El email ". $mensajes["vacio"])))
      ))
      ->add("secs_pass","password", array(
        "label" => "Password",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El password ". $mensajes["obligatorio"])))
      ))
      ->add("re_pass","password", array(
        "label" => "Repita el password"
      ))
      ->add("primercontacto","hidden", array(
        "label" => "Repita el password",
        "data" => "1"
      ))
      ->add("id_estatus","hidden", array(
        "label" => "Repita el password",
        "data" => 1
      ))
      ->add("fecha_registro","hidden", array(
        "label" => "Repita el password",
        "data" => 'date'
      ))
      ->add("creador","hidden", array(
        "label" => "Repita el password",
        "data" => 'id_creador'
      ))
      ->add("fecha_actualizacion","hidden", array(
        "label" => "Repita el password",
        "data" => 'fecha'
      ))
      ->add("actualizador","hidden", array(
        "label" => "Repita el password",
        "data" => 'id_actualizador'
      ))
      ->add("Guardar","submit", array(
        "attr" => array(
          "class" => "btn btn-primary"
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
