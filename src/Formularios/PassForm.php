<?php

namespace Formularios;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Servicios;

class PassForm implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['passForm'] = $app->protect(function () use ($app) {

      $app->register(new Servicios\Mensajes());
      $mensajes = $app['mensajes']();

      $form = $app['form.factory']->createBuilder('form')
      ->add('pass','password',array(
        'label'=>"Tu password",
        'constraints' => array(new Assert\NotBlank(array('message'=>"El password ". $mensajes["vacio"])))
        ))
      ->add('n_pass','password',array(
        'label' => 'Nuevo password',
        'constraints' => array(new Assert\NotBlank(array('message'=>"El nuevo password ". $mensajes["vacio"])))
        ))
      ->add('r_pass','password', array(
        'label' => 'Repetir el nuevo password',
        'constraints' => array(new Assert\NotBlank(array('message'=>"Tienes que repetir el nuevo")))
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
