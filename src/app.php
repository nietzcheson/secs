<?php

use Silex\Application;

use Silex\ServiceProviderInterface;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\DBAL\Connection;
use Silex\Provider\FormServiceProvider;


$app = new Application();
$app['debug'] = true;
$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallback' => 'en',
));

$app['asset_path'] = $app->share(function () {
  // implement whatever logic you need to determine the asset path

  return 'http://secs.loc/';
});

date_default_timezone_set('America/Cancun');

$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());


$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...
    return $twig;
}));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'dbs.options' => array (
        'mysql_read' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => 'sisfc',
            'user'      => 'sisfc',
            'password'  => 'sisfc',
            'charset'   => 'utf8',
        ),
    ),
));

$app->register(new Silex\Provider\SecurityServiceProvider());



class UserProvider implements UserProviderInterface
{
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function loadUserByUsername($username)
    {

        $stmt = $this->conn->executeQuery('SELECT * FROM prospectos WHERE email_prospecto = ?', array(strtolower($username)));

        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(sprintf('El usuario "%s" no existe.', $username));
        }

        //$user['role'] = array("ROLE_ADMIN");
        return new User($user['email_prospecto'], $user['secs_pass'], explode(',', $user['rol_prospecto']),true,true,true,true);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }

}

$app['security.firewalls'] = array(
    'login' => array(
      'pattern' => '^/login$'
    ),

    'permisos'=> array(
      'pattern' => '/permisos',
      'http' => true,
      'logout' => array('logout_path' => '/admin/logout'),
      'users' => array(
        // la contraseña sin codificar es "foo"
        'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
      ),
    ),
    'secured' => array(
        'pattern' => '^/',
        'form' => array('login_path' => '/login','check_path' => '/admin/login_check'),
        'logout' => array('logout_path' => '/admin/logout'),
        'users' => $app->share(function () use($app){
          return new UserProvider($app['db']);
        }),
    ),
    'unsecured' => array(
      'anonymous' => true,
    ),
);


$app["token"] = function() use($app)
{
  $token = $app['security']->getToken();

  if (null !== $token) {
    $user = $token->getUser();
  }

  return $user;
};

$app["form_perfil"] = function($valor) use($app)
{
  return $valor;
};


$app["id_marca"] = function() use($app)
{
  $token = $app["token"];

  $marca_cliente = $app['db']->fetchAssoc('SELECT id_u_prospecto FROM prospectos WHERE email_prospecto = ? ',array(strtolower($token->getUsername())));
  $marca_cliente = $app['db']->fetchAssoc('SELECT * FROM marcas_clientes WHERE id_u_cliente = ? ',array(strtolower($marca_cliente["id_u_prospecto"])));

  return $marca_cliente["id_u_marca"];
};



class SQLServiceProvider implements ServiceProviderInterface
{

  public function register(Application $app)
  {
    $app["sql"] = $app->protect( function ($sentencia, $table, $data) use ($app){
      switch($sentencia){
        case 'insert':
          $data_array = array_keys($data);

            $cadena1="";
            $cadena2="";
            foreach ($data_array as $value) {
              $cadena1.=$value .",";
              $cadena2.=":".$value .",";
            }

            $cadena1= substr($cadena1, 0, strlen($cadena1)-1);
            $cadena2= substr($cadena2, 0, strlen($cadena2)-1);

            $app["db"]->prepare("
                  INSERT INTO ". $table ."
                  (
              " . $cadena1 . "
            )
                VALUES (
              " . $cadena2 . "
                  )"
                )
                ->execute(
                    $data
            );

            if($app["db"]){
              return true;
            }else{
              return false;
            }

          break;
        case 'update':


        $arra = array_keys($data);
	    	$cadena="";
	    	$prime="";
	    	foreach ($arra as $value) {
	    		if ($prime=="") {
	    			$prime = " WHERE " . $value . " = :"  . $value;
	    		}
	    		else
	    		{
	    			$cadena.=" ". $value . " = :"  . $value .",";
	    		}
	    	}
	    	$cadena= substr($cadena, 0, strlen($cadena)-1). $prime;

	    	$app["db"]->prepare("
            	UPDATE ". $table ." SET
                ".  $cadena
                )
                ->execute(
                    $data
                );

          if($app["db"]){
            return true;
          }else{
            return false;
          }

          break;
      }

    });
  }

  public function boot(Application $app)
  {

  }

}

$app["clientes"] = function() use($app){

  $clientes = $app["db"]->fetchAll("SELECT * FROM marcas");

  return $clientes;
};

$app->register(new SQLServiceProvider());


$app['security.role_hierarchy'] = array(
    'ROLE_ADMIN' => array(
      // ROLES ADMINISTRACIÓN
      'ROLE_CREAR_USUARIOS',
      'ROLE_DAR_PERMISOS',
      // ROLES EDITOR
      'ROLE_CREAR_ORDEN',
      'ROLE_EDITAR_ORDEN',
      'ROLE_ELIMINAR_ORDEN',
      'ROLE_VISUALIZAR_ORDEN',
      'ROLE_CREAR_PROVEEDOR',
      'ROLE_EDITAR_PROVEEDOR',
      'ROLE_ELIMINAR_PROVEEDOR',
      'ROLE_CREAR_CONTACTO_PROVEEDOR',
      'ROLE_EDITAR_CONTACTO_PROVEEDOR',
      'ROLE_ELIMINAR_CONTACTO_PROVEEDOR',
      'ROLE_VISUALIZAR_CONTACTO_PROVEEDOR',
      // ROLES AUXILIAR
      'ROLE_VISUALIZAR_OPERACIONES',
      'ROLE_VISUALIZAR_COTIZACIONES',
      'ROLE_VISUALIZAR_BITACORA',
      'ROLE_VISUALIZAR_ESTADO_CUENTA',
      'ROLE_VISUALIZAR_FACTURAS',
      'ROLE_VISUALIZAR_CALENDARIO_REFERENCIAS',
      'ROLE_DESCARGAR_FACTURAS',
      'ROLE_ENVIAR_FACTURAS',
    ),
    'ROLE_EDITOR' => array(
      // ROLES EDITOR
      'ROLE_CREAR_ORDEN',
      'ROLE_EDITAR_ORDEN',
      'ROLE_ELIMINAR_ORDEN',
      'ROLE_VISUALIZAR_ORDEN',
      'ROLE_CREAR_PROVEEDOR',
      'ROLE_EDITAR_PROVEEDOR',
      'ROLE_ELIMINAR_PROVEEDOR',
      'ROLE_CREAR_CONTACTO_PROVEEDOR',
      'ROLE_EDITAR_CONTACTO_PROVEEDOR',
      'ROLE_ELIMINAR_CONTACTO_PROVEEDOR',
      'ROLE_VISUALIZAR_CONTACTO_PROVEEDOR',
      // ROLES AUXILIAR
      'ROLE_VISUALIZAR_OPERACIONES',
      'ROLE_VISUALIZAR_COTIZACIONES',
      'ROLE_VISUALIZAR_BITACORA',
      'ROLE_VISUALIZAR_ESTADO_CUENTA',
      'ROLE_VISUALIZAR_FACTURAS',
      'ROLE_VISUALIZAR_CALENDARIO_REFERENCIAS',
      'ROLE_DESCARGAR_FACTURAS',
      'ROLE_ENVIAR_FACTURAS',
    ),
    'ROLE_AUX' => array(
      // ROLES AUXILIAR
      'ROLE_VISUALIZAR_OPERACIONES',
      'ROLE_VISUALIZAR_COTIZACIONES',
      'ROLE_VISUALIZAR_BITACORA',
      'ROLE_VISUALIZAR_ESTADO_CUENTA',
      'ROLE_VISUALIZAR_FACTURAS',
      'ROLE_VISUALIZAR_CALENDARIO_REFERENCIAS',
      'ROLE_DESCARGAR_FACTURAS',
      'ROLE_ENVIAR_FACTURAS',
    ),
);

return $app;
