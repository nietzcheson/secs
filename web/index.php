<?php

//ini_set('display_errors', 0);

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/prod.php';


require __DIR__.'/../src/controllers.php';
require __DIR__.'/../src/Librerias/dompdf/dompdf_config.inc.php';
require __DIR__.'/../src/Librerias/nusoap/nusoap.php';


$app->run();
