<?php

/**
 * Web Routes
 * 
 * This file defines the routes for the web interface
 */

use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Controllers\BlogController;
use App\Middleware\ExampleMiddleware;
use Framework\Routing\Router;

// Get the router instance
$router = app('router');

// Define routes with anonymous functions



