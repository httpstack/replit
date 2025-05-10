<?php
/**
 * Application entry point
 * 
 * This file bootstraps the application and starts the request handling process.
 */

// Define the base path constant
define('BASE_PATH', dirname(__DIR__));

// Load Composer's autoloader
require BASE_PATH . '/vendor/autoload.php';

// Bootstrap the application
$app = require_once BASE_PATH . '/src/Core/Bootstrap.php';
$router = $app->get('router');
$router->get("/home", ["App\Controllers\HomeController","index"]);
// Run the application
$app->run();
