<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;

class RazonSocialForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['razonSocialForm'] = $app->protect(function ($data = false) use ($app) {

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $personas = $app["db"]->fetchAll("SELECT * FROM tipo_persona");

      $tipos = array();

      foreach($personas as $tipo){
        $tipos[$tipo['id_tipo_persona']] = $tipo['tipo_persona'];
      }

      $defaultTipo = $data["tipo_persona"];
      if(!$data){
        $data = array();
        $defaultTipo = "";
      }

      $resultado = $app["db"]->fetchAll("SELECT * FROM paises");

      $paises = array();
      foreach($resultado as $pais){
        $paises[$pais['id_pais']] = $pais["nombre_pais"];
      }


      $form = $app['form.factory']->createBuilder('form', $data)
      ->add('tipo_persona','choice', array(
        'choices' => $tipos,
        "empty_value" => "Seleccione el tipo de persona",
        "data" => $defaultTipo,
        "label" => "Tipo de persona*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Tiene que escoger un tipo de role ")))
      ))
      ->add("razon_social","text", array(
        "label" => "Razón social*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El nombre de la razón social ". $mensajes["vacio"])))
      ))
      ->add("rfc","text", array(
        "label" => "RFC*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El el RFC es ". $mensajes["obligatorio"])))
      ))
      ->add("email","text", array(
        "label" => "Email facturación*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El Email ". $mensajes["obligatorio"])))
      ))
      ->add("domicilio_fiscal","textarea", array(
        "label" => "Domicilio Fiscal*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El domicilio fiscal ". $mensajes["vacio"])))
      ))
      ->add("calle","text", array(
        "label" => "Calle*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"La calle ". $mensajes["obligatorio"])))
      ))
      ->add("num_ext","text", array(
        "label" => "Número Externo",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El número externo ". $mensajes["obligatorio"])))

      ))
      ->add("num_int","text", array(
        "label" => "Número Interno",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El número interno ". $mensajes["obligatorio"])))
      ))
      ->add("pais","choice", array(
        "label" => "País",
        'choices' => $paises,
        "empty_value" => "Seleccione el país",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Seleccione el país")))
      ))
      ->add("estado","text", array(
        "label" => "Estado",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Este campo ". $mensajes["obligatorio"])))
      ))
      ->add("municipio","text", array(
        "label" => "Municipio",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Este campo ". $mensajes["vacio"])))
      ))
      ->add("ciudad","text", array(
        "label" => "Ciudad",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El campo de la ciudad ". $mensajes["obligatorio"])))

      ))
      ->add("colonia","text", array(
        "label" => "Colonia",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Este campo ". $mensajes["vacio"])))
      ))
      ->add("cp","text", array(
        "label" => "Código postal",
        'constraints' => array(new Assert\NotBlank(array('message'=>"Este campo ". $mensajes["obligatorio"])))

      ))
      ->add("fecha_creacion","hidden", array(
        'data' => date('d/m/Y')
      ))
      ->add("acta","hidden", array(
        'data' => true
      ))
      ->add("poder_notarial","hidden", array(
        'data' => true
      ))
      ->add("rppc","hidden", array(
        'data' => true
      ))
      ->add("check_rfc","hidden", array(
        'data' => true
      ))
      ->add("r1","hidden", array(
        'data' => true
      ))
      ->add("r2","hidden", array(
        'data' => true
      ))
      ->add("comp_domicilio","hidden", array(
        'data' => true
      ))
      ->add("id_representante","hidden", array(
        'data' => true
      ))
      ->add("curp","hidden", array(
        'data' => true
      ))
      ->add("otro","hidden", array(
        'data' => true
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
