<?php
/**
 * Application entry point
 * 
 * This file bootstraps the application and starts the request handling process.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Framework\Core\Application;

// Create the application instance
$app = new Application(__DIR__ . '/../');

// Set environment and debug mode
$config = $app->getContainer()->make('config');
$app->setEnvironment($config['app']['env'] ?? 'production');
$app->setDebug($config['app']['debug'] ?? false);

// Load routes
require_once $app->routesPath('web.php');

// Boot and run the application
$app->boot();
$app->run();
