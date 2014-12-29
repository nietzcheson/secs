<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;

class CuentaForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['cuentaForm'] = $app->protect(function () use ($app) {

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $token = $app["token"];

      $id_marca = $app["id_marca"];

      $marca = $app["db"]->fetchAssoc("SELECT * FROM marcas WHERE id_u_marca=:id_u_marca", array(
        "id_u_marca" => $id_marca
      ));

      $form = $app['form.factory']->createBuilder('form',$marca)
      ->add('nombre_marca','text',array(
        'label' => 'Nombre (Empresa / Compañía)*',
        'constraints' => array(new Assert\NotBlank(array('message'=>"El nombre de la empresa ")))

      ))
      ->add('email','text',array(
        'label' => 'Email general*',
        'constraints' => array(new Assert\NotBlank(array('message'=>"El nombre de la empresa ". $mensajes["vacio"])))
      ))
      ->add('telefono1','text',array(
        'label' => 'Teléfono*'
      ))
      ->add('web','text')
      ->add('facebook')
      ->add('twitter')
      ->add('observaciones','textarea')
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
