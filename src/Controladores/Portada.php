<?php
namespace Controladores;

use Silex\Application;
use Silex\ControllerProviderInterface;

class Portada implements ControllerProviderInterface
{
  public function connect(Application $app)
  {

    $controllers = $app['controllers_factory'];

    $controllers->get('/', function () use ($app) {
      return $app->redirect("perfil");
    })
    ->bind('homepage')
    ;

    return $controllers;
  }
}

?>
