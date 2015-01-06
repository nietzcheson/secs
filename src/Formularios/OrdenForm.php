<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;

class OrdenForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['ordenForm'] = $app->protect(function ($datos = false) use ($app) {

      $disabled = false;
      $title_button = "Agregar productos";
      if($datos != false){
        $disabled = true;
        $title_button = "";
      }else{
        $datos = array();
      }

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $token = $app["token"];

      $proveedores = $app["db"]->fetchAll("SELECT * FROM proveedores");

      $proveedor = array();
      foreach($proveedores as $_proveedor){
        $proveedor[$_proveedor['id_u_proveedor']] = $_proveedor["proveedor"];
      }

      $referencias = $app["db"]->fetchAll("SELECT * FROM referencias Where cliente = '{$app["id_marca"]}' ORDER BY id_referencia DESC");

      $_referencias = array("x"=> "Sin asignar");
      foreach($referencias as $referencia){
        $_referencias[$referencia["id_u_referencia"]] = $referencia["id_u_referencia"];
      }

      //$form = $app["form_perfil"]($usuario);
      $form = $app['form.factory']->createBuilder('form', $datos)
      ->add('id_u_proveedor',"choice", array(
        'label'      => "Proveedores",
        "choices" => $proveedor,
        "empty_value" => "Seleccione",
        "attr" => array("disabled"=> $disabled),
        'constraints' => array(new Assert\NotBlank(array('message'=>'Seleccione un proveedor')))
      ))
      ->add('numero_factura', 'text', array(
        'label'      => "# Factura",
        'attr' => array('placeholder' => 'Número de factura',"disabled"=> $disabled),
        'constraints' => array(new Assert\NotBlank(array('message'=>"El número de factura ". $mensajes["vacio"])))
      ))
      ->add('id_u_referencia', 'choice', array(
        'label'      => "Asignar a una referencia",
        "empty_value" => "Seleccione",
        'choices' => $_referencias,
        "attr" => array("disabled"=> $disabled),
        'constraints' => array(new Assert\NotBlank(array('message'=>"Seleccione una referencia")))
      ))
      ->add($title_button,"submit", array(
        'attr'=>array(
            'class'=>'btn btn-default',
            "disabled"=> $disabled
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
