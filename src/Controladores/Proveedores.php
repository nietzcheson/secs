<?php
namespace Controladores;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\FormServiceProvider;
use Formularios;

class Proveedores implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = $app['controllers_factory'];

    $controllers->match('/', function (Request $request) use ($app) {

      $proveedores = $app["db"]->fetchAll(
        "SELECT *
          FROM proveedores pr LEFT JOIN clientes_proveedores cp
          ON pr.id_u_proveedor = cp.proveedor_id
          WHERE cliente_id = '{$app["id_marca"]}'
        ");

      return $app['twig']->render('views/proveedores.html',
        array(
          'titulo' => "Proveedores",
          'proveedores' => $proveedores,
          'btn_header' => array(
            'titulo' => "Crear proveedor",
            'href' => $app['url_generator']->generate('crear_proveedor'),
            'icon' => ''
          )
        )
      );

    })
    ->bind('proveedores')
    ;

    $controllers->match('crear-proveedor', function (Request $request) use ($app) {

      $app->register(new Formularios\ProveedoresForm());
      $form = $app['proveedoresForm']();

      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){
          $data = $form->getData();

          $data["id_u_proveedor"] = "";
          $sql = $app["sql"]("insert","proveedores",$data);

          $id_proveedor = $app["db"]->fetchAssoc("SELECT * FROM proveedores ORDER BY id_proveedor DESC");

          $updateID = array(
            'id_proveedor' => $id_proveedor['id_proveedor'],
            'id_u_proveedor' => $id_proveedor['id_proveedor']
          );

          $sql = $app["sql"]("update","proveedores",$updateID);

          $clientes_proveedores = array(
            "cliente_id" => $app["id_marca"],
            "proveedor_id" => $id_proveedor['id_proveedor']
          );

          $sql = $app["sql"]("insert","clientes_proveedores",$clientes_proveedores);


          return $app->redirect($app['url_generator']->generate('proveedor', array('id' => $id_proveedor["id_proveedor"])));
        }

      }

      return $app['twig']->render('views/crear-proveedor.html',
        array(
          "form"   => $form->createView(),
          'titulo' => 'Crear proveedor',
        )
      );

    })
    ->bind('crear_proveedor')
    ;

    $controllers->match("proveedor/{id}", function ($id, Request $request) use($app){

      $proveedor = $app["db"]->fetchAssoc("SELECT * FROM proveedores WHERE id_u_proveedor= '{$id}'");

      $app->register(new Formularios\ProveedoresForm());

      $form = $app['proveedoresForm']($proveedor);

      $_mensaje = "";

      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){
          $data = $form->getData();

          $sql = $app["sql"]("update","proveedores",$data);
          $_mensaje = "Los datos se han actualizado correctamente...";
        }

      }

      return $app['twig']->render('views/proveedor.html', array(
        "form"=>$form->createView(),
        "id_proveedor" => $id,
        "titulo" => $proveedor["proveedor"],
        'subtitulo' => array(
          'titulo' => 'Perfil proveedor',
          'color_box' => '#7266ba'
          ),
        '_mensaje' => $_mensaje,
      ));

    })->bind("proveedor");

    $controllers->match("eliminar-proveedor/{id}", function ($id) use($app){


      $cliente_proveedor = $app["db"]->executeQuery("DELETE FROM clientes_proveedores WHERE cliente_id='{$app["id_marca"]}' AND proveedor_id ='{$id}' ");

      if(!$cliente_proveedor){
        return $app->redirect($app['url_generator']->generate('proveedores'));
      }else{
        $app["db"]->executeQuery("DELETE FROM proveedores WHERE id_u_proveedor='{$id}' ");
        return $app->redirect($app['url_generator']->generate('proveedores'));
      }


    })->bind('eliminar_proveedor');

    $controllers->match("proveedor/{proveedor}/contactos", function ($proveedor) use($app){

      $_proveedor = $app["db"]->fetchAssoc("SELECT * FROM proveedores WHERE id_u_proveedor= '{$proveedor}'");
      $contactos = $app["db"]->fetchAll("SELECT * FROM contactos_proveedor WHERE id_u_proveedor = '{$proveedor}' AND activo =1 ");

      // echo "<pre>";print_r($contactos);
      // exit();

      return $app['twig']->render('views/contactos-proveedor.html', array(
        //"form"=>$form->createView(),
        "id_proveedor" => $proveedor,
        'contactos' => $contactos,
        "titulo" => $_proveedor["proveedor"],
        'subtitulo' => array(
          'titulo' => 'Contactos del proveedor',
          'color_box' => '#23b7e5'
        ),
        //'_mensaje' => $_mensaje
      ));
    })->bind('contactos_proveedor');

    $controllers->match('proveedor/{proveedor}/crear-contacto', function ($proveedor, Request $request) use ($app) {

      $app->register(new Formularios\ContactoProveedorForm());
      $form = $app['contactoProveedorForm']();

      $_proveedor = $app["db"]->fetchAssoc("SELECT * FROM proveedores WHERE id_u_proveedor= '{$proveedor}'");


      $_mensaje = "";
      $_error = "";
      if('POST' == $request->getMethod()){
        $form->bind($request);

        if($form->isValid()){
          $data = $form->getData();

          $data["id_u_proveedor"] = $proveedor;
          $data["id_u_contacto_p"] = "";

          $sql = $app["sql"]("insert","contactos_proveedor",$data);

          $id_contacto = $app["db"]->fetchAssoc("SELECT * FROM contactos_proveedor ORDER BY id_contacto DESC");

          $updateID = array(
            'id_contacto' => $id_contacto['id_contacto'],
            'id_u_contacto_p' => $id_contacto['id_contacto']
          );

          $sql = $app["sql"]("update","contactos_proveedor",$updateID);

        }
      }

        return $app['twig']->render('views/crear-contacto-proveedor.html', array(
        "form"=>$form->createView(),
        "id_proveedor" => $proveedor,
        "titulo" => $_proveedor["proveedor"],
        'subtitulo' => array(
        'titulo' => 'Crear contacto',
        'color_box' => '#23b7e5'
        ),
        '_error' => $_error,
        ));

      return "Crear contacto del proveedor";


      })->bind('crear_contacto_proveedor');

      $controllers->match('proveedor/{proveedor}/contacto/{contacto}', function ($proveedor, $contacto, Request $request) use ($app) {

        $_proveedor = $app["db"]->fetchAssoc("SELECT * FROM proveedores WHERE id_u_proveedor= '{$proveedor}'");
        $_contacto = $app["db"]->fetchAssoc("SELECT * FROM contactos_proveedor WHERE id_contacto= '{$contacto}'");

        $app->register(new Formularios\ContactoProveedorForm());
        $form = $app['contactoProveedorForm']($_contacto);

        $_mensaje = "";
        $_error = "";
        if('POST' == $request->getMethod()){
          $form->bind($request);

          if($form->isValid()){
            $data = $form->getData();

            $sql = $app["sql"]("update","contactos_proveedor",$data);

            if($sql){
              $_mensaje = "Los datos se han actualizado";
            }
          }
        }

        return $app['twig']->render('views/contacto-proveedor.html', array(
          "form"=>$form->createView(),
          "titulo" => $_proveedor["proveedor"],
          "id_proveedor" => $proveedor,
          'subtitulo' => array(
          'titulo' => "Contacto del proveedor",
          'color_box' => '#f05050'
          ),
          '_error' => $_error,
          '_mensaje' => $_mensaje
        ));


      })->bind('contacto_proveedor');

      $controllers->get("proveedor/{id_proveedor}/eliminar-contacto/{id_contacto}", function($id_proveedor, $id_contacto) use($app){


        $contacto_proveedor = $app["db"]->fetchAssoc("SELECT *
          FROM contactos_proveedor cp LEFT JOIN clientes_proveedores clp
          ON cp.id_u_proveedor = clp.proveedor_id
          WHERE id_contacto = '{$id_contacto}'
          AND clp.cliente_id = '{$app["id_marca"]}'

        ");


          if(empty($contacto_proveedor)){
            return $app->redirect($app['url_generator']->generate('contactos_proveedor', array("proveedor"=>$id_proveedor)));
          }

          $clientes_proveedores = $app["db"]->executeQuery("UPDATE contactos_proveedor SET activo = 0 WHERE id_contacto='{$id_contacto}' ");

          return $app->redirect($app['url_generator']->generate('contactos_proveedor', array("proveedor"=>$id_proveedor)));

        })->bind('eliminar_contacto_proveedor');


        $controllers->match("proveedor/{proveedor}/productos", function ($proveedor) use($app){

          $_proveedor = $app["db"]->fetchAssoc("SELECT * FROM proveedores WHERE id_u_proveedor= '{$proveedor}'");
          $productos = $app["db"]->fetchAll("SELECT * FROM productos WHERE id_u_proveedor = '{$proveedor}' AND activo =1 ");

          // echo "<pre>";print_r($contactos);
          // exit();

          return $app['twig']->render('views/productos-proveedor.html', array(
            //"form"=>$form->createView(),
            "id_proveedor" => $proveedor,
            'productos' => $productos,
            "titulo" => $_proveedor["proveedor"],
            'subtitulo' => array(
              'titulo' => 'Productos del proveedor',
              'color_box' => '#f05050'
            ),
            //'_mensaje' => $_mensaje
          ));
        })->bind('productos_proveedor');

        $controllers->match('proveedor/{proveedor}/crear-producto', function ($proveedor, Request $request) use ($app) {

          $app->register(new Formularios\ProductoProveedorForm());
          $form = $app['productoProveedorForm']();

          $_proveedor = $app["db"]->fetchAssoc("SELECT * FROM proveedores WHERE id_u_proveedor= '{$proveedor}'");


          $_mensaje = "";
          $_error = "";
          if('POST' == $request->getMethod()){
            $form->bind($request);

            if($form->isValid()){
              $data = $form->getData();

              $data["id_u_proveedor"] = $proveedor;
              $data["id_u_producto"] = "";

              $sql = $app["sql"]("insert","productos",$data);

              if($sql){
                $id_producto = $app["db"]->fetchAssoc("SELECT * FROM productos ORDER BY id_producto DESC");

                $updateID = array(
                  'id_producto' => $id_producto['id_producto'],
                  'id_u_producto' => $id_producto['id_producto']
                );

                $sql = $app["sql"]("update","productos",$updateID);
              }

              //return $app->redirect($app['url_generator']->generate('productos_proveedor', array("proveedor"=>$proveedor)));


            }
          }

          return $app['twig']->render('views/crear-producto-proveedor.html', array(
            "form"=>$form->createView(),
            "id_proveedor" => $proveedor,
            "titulo" => $_proveedor["proveedor"],
            'subtitulo' => array(
              'titulo' => 'Crear producto',
              'color_box' => '#f05050'
            ),
            '_error' => $_error,
          ));

          return "Crear contacto del proveedor";


        })->bind('crear_producto_proveedor');

        $controllers->match('proveedor/{proveedor}/producto/{producto}', function ($proveedor, $producto, Request $request) use ($app) {

          $_proveedor = $app["db"]->fetchAssoc("SELECT * FROM proveedores WHERE id_u_proveedor= '{$proveedor}'");
          $_producto = $app["db"]->fetchAssoc("SELECT * FROM productos WHERE id_producto= '{$producto}'");


          $app->register(new Formularios\ProductoProveedorForm());
          $form = $app['productoProveedorForm']($_producto);

          $_mensaje = "";
          $_error = "";
          if('POST' == $request->getMethod()){
            $form->bind($request);

            if($form->isValid()){
              $data = $form->getData();

              $sql = $app["sql"]("update","productos",$data);

              if($sql){
                $_mensaje = "Los datos se han actualizado";
              }
            }
          }

          return $app['twig']->render('views/contacto-proveedor.html', array(
            "form"=>$form->createView(),
            "titulo" => $_proveedor["proveedor"],
            "id_proveedor" => $proveedor,
            'subtitulo' => array(
              'titulo' => "Contacto del proveedor",
              'color_box' => '#f05050'
            ),
            '_error' => $_error,
            '_mensaje' => $_mensaje
          ));


        })->bind('producto_proveedor');

        $controllers->get("proveedor/{proveedor}/eliminar-producto/{producto}", function($proveedor, $producto) use($app){


          $producto_proveedor = $app["db"]->fetchAssoc("SELECT *
            FROM productos p LEFT JOIN clientes_proveedores clp
            ON p.id_u_proveedor = clp.proveedor_id
            WHERE id_producto = '{$producto}'
            AND clp.cliente_id = '{$app["id_marca"]}'
            ");




            if(empty($producto_proveedor)){
              return $app->redirect($app['url_generator']->generate('productos_proveedor', array("proveedor"=>$proveedor)));
            }



            $productos_proveedor = $app["db"]->executeQuery("UPDATE productos SET activo = 0 WHERE id_u_producto='{$producto}' ");

            return $app->redirect($app['url_generator']->generate('productos_proveedor', array("proveedor"=>$proveedor)));

          })->bind('eliminar_producto_proveedor');

    return $controllers;
  }



}

?>
