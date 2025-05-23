<?php

/**
 * Application entry point.
 *
 * This file bootstraps the application and starts the request handling process.
 */
define('DOC_ROOT', '/var/www/html/replit');
require_once __DIR__.'/../vendor/autoload.php';

use Framework\Core\Application;

// Create the application instance

$app = new Application(DOC_ROOT."/config", true);

    

// Load routes
require_once $app->routesPath('web.php');

// Boot and run the application
$app->boot();
$app->run();