-- Active: 1738042425571@@127.0.0.1@3306@cmcintosh
-- Active: 1745000722378@@193.203.166.76@3306@u373556446_httpstack
<?php

/**
 * Web Routes.
 *
 * This file defines the routes for the web interface
 */

use App\Controllers\HomeController;
use App\Middleware\TemplateMiddleware;

// Get the router instance from the application container
$router = $app->getContainer()->make('router');
// $router->middleware(TemplateMiddleware::class);
// Define routes
$router->get('/home', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
// Simple route with a closure
$router->get('/', function () {
    return '<h1>Welcome to the Home Page</h1>';
});
$router->any('{any}', function () {
    return view('errors/404', [
        'title' => 'Page Not Found',
    ], 404);
})->where('any', '.*');