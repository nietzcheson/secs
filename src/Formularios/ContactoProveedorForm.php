<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;

class ContactoProveedorForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['contactoProveedorForm'] = $app->protect(function ($data = false) use ($app) {

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $paises = $app["db"]->fetchAll("SELECT * FROM paises");

      $_paises = array();

      foreach($paises as $pais){
        $_paises[$pais['id_pais']] = $pais['nombre_pais'];
      }

      if(!$data){
        $data = array();
      }


      $form = $app['form.factory']->createBuilder('form', $data)
      ->add('nombre_contacto','text', array(
        "label" => "Nombre del contacto*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El nombre ".$mensajes['vacio'])))
      ))
      ->add("apellido_contacto","text", array(
        "label" => "Apellido contacto*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El apellido ". $mensajes["vacio"])))
      ))
      ->add("telefono","text", array(
        "label" => "Teléfono",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El teléfono ". $mensajes["obligatorio"])))
      ))
      ->add("celular","text", array(
        "label" => "Celular",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El celular ". $mensajes["vacio"])))
      ))
      ->add("email","text", array(
        "label" => "Email",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El email ". $mensajes["obligatorio"])))
        ))
      ->add("pais","choice", array(
        "choices" => $_paises,
        "empty_value" => "Seleccione",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El país ". $mensajes["vacio"])))
      ))
      ->add("estado","text", array(
        "label" => "Estado*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El Estado ". $mensajes["obligatorio"])))
      ))
      ->add("ciudad","text", array(
        "label" => "Ciudad*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"La ciudad ". $mensajes["obligatorio"])))
        ))
      ->add("fecha_creacion","hidden", array(
        "data" => date('d/m/Y'),
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
