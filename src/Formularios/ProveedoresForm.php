<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;

class ProveedoresForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['proveedoresForm'] = $app->protect(function ($data = false) use ($app) {

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $clasificacion_proveedores = $app["db"]->fetchAll("SELECT * FROM clasificacion_proveedores ORDER BY nombre_clasificacion ASC");
      $clasificaciones = array();

      foreach($clasificacion_proveedores as $clasificacion){
        $clasificaciones[$clasificacion['id_clasificacion']] = $clasificacion['nombre_clasificacion'];
      }

      $tipo_persona = $app["db"]->fetchAll("SELECT * FROM tipo_persona ORDER BY tipo_persona ASC");
      $tipos = array();



      foreach($tipo_persona as $tipo){
        $tipos[$tipo['id_tipo_persona']] = $tipo['tipo_persona'];
      }

      if(!$data){
        $data = array();
      }

      $paises = $app["db"]->fetchAll("SELECT * FROM paises");

      $_paises = array();
      foreach($paises as $pais){
        $_paises[$pais['id_pais']] = $pais["nombre_pais"];
      }


      $form = $app['form.factory']->createBuilder('form', $data)
      ->add('clasificacion','choice', array(
        'choices' => $clasificaciones,
        "empty_value" => "Seleccione",
        "label" => "Clasificación proveedor*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Tiene que escoger la clasificación del Proveedor ")))
      ))
      ->add("tipo_persona","choice", array(
        'choices' => $tipos,
        "empty_value" => "Seleccione",
        "label" => "Tipo de persona*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Seleccione el tipo de persona ")))
      ))
      ->add("pais","choice", array(
        'choices' => $_paises,
        'empty_value' => "Seleccione",
        "label" => "País",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El país ". $mensajes["obligatorio"])))
      ))
      ->add("proveedor","text", array(
        "label" => "proveedor",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El nombre del proveedor ". $mensajes["obligatorio"])))
      ))
      ->add("razon_social","text", array(
        "label" => "Razón social",
        'constraints' => array(new Assert\NotBlank(array('message'=>"La razón social ". $mensajes["vacio"])))
      ))
      ->add("direccion","text", array(
        "label" => "Dirección",
        'constraints' => array(new Assert\NotBlank(array('message'=>"La dirección ". $mensajes["obligatorio"])))
      ))
      ->add("telefono","text", array(
        "label" => "Teléfono",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El teléfono ". $mensajes["obligatorio"])))

      ))
      ->add("rfc_tax","text", array(
        "label" => "RFC o TAX ID",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El RFC o TAX ID ". $mensajes["vacio"])))

      ))
      ->add("domicilio_fiscal","text", array(
        "label" => "Domicilio fiscal",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El domicilio fiscal ". $mensajes["obligatorio"])))

      ))
      ->add("datos_bancarios","text", array(
        "label" => "Datos bancarios",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Los datos bancarios ". $mensajes["obligatorio"])))
      ))
      ->add("usuario_creador","hidden", array(
        'data' => 'Usuario'
      ))
      ->add("fecha_creacion","hidden", array(
        'data' => 'Fecha'
      ))
      ->add("usuario_actualizador","hidden", array(
        'data' => 'Actualizador'
      ))
      ->add("ultima_actualizacion","hidden", array(
        'data' => 'Actualizacion'
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
