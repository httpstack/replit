<?php

/**
 * Application entry point.
 *
 * This file bootstraps the application and starts the request handling process.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Framework\Core\Application;

// Create the application instance
$paths = [
    'basePath' => __DIR__.'/..',
    'appPath' => '/app',
    'configPath' => '/config',
    'routesPath' => '/routes',
    'templatesPath' => '/templates',
    'assetsPath' => '/assets',
];
$app = new Application($paths, true);

$dm = $app->getContainer()->make('directoryMapper');
debug($dm->getFiles("/var/www/html/replit/app"));
// Set environment and debug mode
$config = $app->getContainer()->make('config');

// Load routes
require_once $app->routesPath('web.php');

// Boot and run the application
$app->boot();
$app->run();