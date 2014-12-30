<?php

namespace Servicios;


use Silex\Application;
use Silex\ServiceProviderInterface;


class PDF implements ServiceProviderInterface
{
  public function register(Application $app)
  {
    $app['pdf'] = $app->protect(function () use ($app) {


    return "el pdf";

    });
  }

  public function boot(Application $app)
  {
  }
}


 ?>
