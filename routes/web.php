<?php

/**
 * Web Routes.
 *
 * This file defines the routes for the web interface
 */

use App\Controllers\HomeController;
use App\Controllers\ResumeController;
use App\Controllers\UserController;
use App\Middleware\TemplateMiddleware;

// Get the router instance from the application container
$router = $app->getContainer()->make('router');
$router->middleware(TemplateMiddleware::class);
// Define routes
$router->get('/home', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/resume', [ResumeController::class, 'index']);