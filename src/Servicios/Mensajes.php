<?php

namespace Servicios;


use Silex\Application;
use Silex\ServiceProviderInterface;


class Mensajes implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['mensajes'] = $app->protect(function () use ($app) {

      $mensajes = array(
        "vacio" => "no puede quedar vacío",
        "obligatorio" => "es obligatorio"
      );

      return $mensajes;
    });
  }

  public function boot(Application $app)
  {
  }
}


 ?>
