<?php

/**
 * Web Routes
 * 
 * This file defines the routes for the web interface
 */

use App\Controllers\HomeController;
use App\Middleware\ExampleMiddleware;
use Framework\Routing\Router;

// Get the router instance
$router = app('router');

// Define routes with anonymous functions
$router->get('/', function () {
    return view('home/index', [
        'title' => 'Custom MVC Framework',
        'message' => 'Welcome to your custom PHP MVC framework',
        'features' => [
            'MVC Architecture',
            'Service Container',
            'Advanced Routing',
            'Middleware Support',
            'DOM-based Templating',
            'Advanced File Loading',
            'Data Binding',
        ]
    ]);
})->name('home');

// Define routes with controller methods
$router->get('/about', [HomeController::class, 'about'])->name('about');

// Define routes with controller@method string syntax
$router->get('/api', 'App\Controllers\HomeController@api')->name('api');

// Define routes with middleware
$router->get('/dashboard', function () {
    return view('dashboard/index', [
        'title' => 'Dashboard',
    ]);
})->middleware(ExampleMiddleware::class)->name('dashboard');

// Define route groups
$router->group(['prefix' => 'admin', 'middleware' => [ExampleMiddleware::class]], function (Router $router) {
    $router->get('/', function () {
        return view('admin/index', [
            'title' => 'Admin Dashboard',
        ]);
    })->name('admin.dashboard');
    
    $router->get('/users', function () {
        return view('admin/users', [
            'title' => 'Manage Users',
        ]);
    })->name('admin.users');
});

// Define routes with parameters
$router->get('/user/{id}', function ($id) {
    return view('user/profile', [
        'title' => 'User Profile',
        'id' => $id,
    ]);
})->name('user.profile');

// Define routes with optional parameters
$router->get('/post/{slug?}', function ($slug = null) {
    if ($slug === null) {
        return view('post/index', [
            'title' => 'All Posts',
        ]);
    }
    
    return view('post/show', [
        'title' => 'Post Details',
        'slug' => $slug,
    ]);
})->name('post.show');

// Define routes with HTTP methods
$router->post('/contact', [HomeController::class, 'contact'])->name('contact.submit');
$router->get('/contact', [HomeController::class, 'contact'])->name('contact');

// 404 fallback route
$router->any('{any}', function () {
    return view('errors/404', [
        'title' => 'Page Not Found',
    ], 404);
})->where('any', '.*');
