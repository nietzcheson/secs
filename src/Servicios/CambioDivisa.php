<?php

namespace Servicios;


use Silex\Application;
use Silex\ServiceProviderInterface;


class CambioDivisa implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['cambioDivisa'] = $app->protect(function () use ($app) {

      // private function cambio_divisa($valor,$divisa_de_valor,$divisa_a_convertir){
      //   return $valor * $divisa_de_valor / $divisa_a_convertir;
      // }


      return "Cambio de divisa";

    });
  }

  public function boot(Application $app)
  {
  }
}


 ?>
