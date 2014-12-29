<?php

namespace src;


class BlogControllerProvider implements ControllerProviderInterface
{
  public function connect(Application $app)
  {
    // crea un nuevo controlador para la ruta por defecto
    $controllers = $app['controllers_factory'];

    $controllers->get('/', function (Application $app) {
      return $app->redirect('/hello');
    });

    return $controllers;
  }
}


 ?>
