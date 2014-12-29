<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;

class ProductoProveedorForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['productoProveedorForm'] = $app->protect(function ($data = false) use ($app) {

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $paises = $app["db"]->fetchAll("SELECT * FROM paises");

      $_paises = array();

      foreach($paises as $pais){
        $_paises[$pais['id_pais']] = $pais['nombre_pais'];
      }

      $unidades_medida = $app["db"]->fetchAll("SELECT * FROM unidades_medida");

      $unidades = array();

      foreach($unidades_medida as $unidad){
        $unidades[$unidad['id']] = $unidad["nombre_medida"];
      }


      $tipo_fraccion = 0;
      $capitulo = 0;
      $partida = 0;
      $fraccion = 0;
      $subpartida = 0;


      if(!$data){
        $data = array();
      }else{

        $tipo_fraccion = $data["tipo_fraccion"];
        $capitulo = $data["capitulo"];
        $partida = $data["partida"];
        $fraccion = $data["fraccion"];
        $subpartida = $data["subpartida"];
      }

      $form = $app['form.factory']->createBuilder('form', $data)
      ->add('codigo_producto','text', array(
        "label" => "Código del producto - SKU*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El código del producto ".$mensajes['vacio'])))
      ))
      ->add("nombre_producto","text", array(
        "label" => "Producto*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El producto ". $mensajes["vacio"])))
      ))
      ->add("descripcion_producto","textarea", array(
        "label" => "Descripción*",
        'constraints' => array(new Assert\NotBlank(array('message'=>"La descripción ". $mensajes["obligatorio"])))
      ))
      ->add("id_unidadmedida","choice", array(
        "choices" => $unidades,
        'label' => "Unidad de medida*",
        "empty_value" => "Seleccione",
        'constraints' => array(new Assert\NotBlank(array('message'=>"La unidad de medida ". $mensajes["obligatorio"])))
        ))
      ->add("pais_origen","choice", array(
        "choices" => $_paises,
        'label' => "País de origen*",
        "empty_value" => "Seleccione",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El país ". $mensajes["vacio"])))
        ))
        ->add("tipo_fraccion","hidden", array(
          "data" => $tipo_fraccion,
        ))
        ->add("capitulo","hidden", array(
          "data" => $capitulo,
        ))
        ->add("partida","hidden", array(
          "data" => $partida,
        ))
        ->add("fraccion","hidden", array(
          "data" => $fraccion,
        ))
        ->add("subpartida","hidden", array(
          "data" => $subpartida,
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
