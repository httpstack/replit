<?php

$router->get('/about', [HomeController::class, 'about']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->get('/users', [UserController::class, 'index']);
// Simple route with a closure
$router->any('{any}', function () {
    return view('errors/404', [
        'title' => 'Page Not Found',
    ], 404);
})->where('any', '.*');

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

// Blog routes
$router->group(['prefix' => 'blog'], function (Router $router) {
    // Blog home page
    $router->get('/', [BlogController::class, 'index'])->name('blog.index');

    // Search blog posts
    $router->get('/search', [BlogController::class, 'search'])->name('blog.search');

    // View posts by category
    $router->get('/category/{category}', [BlogController::class, 'category'])->name('blog.category');

    // View individual blog post (must be last to avoid conflicts)
    $router->get('/{slug}', [BlogController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('blog.show');
});

// User routes
$router->group(['prefix' => 'users'], function (Router $router) {
    // List all users from database (MySQL example)
    $router->get('/', [UserController::class, 'index'])->name('users.index');

    // Show the create user form
    $router->get('/create', function () {
        return view('users/create', [
            'title' => 'Create User',
            'errors' => [],
            'data' => [],
        ]);
    })->name('users.create');

    // Process the new user form submission
    $router->post('/', [UserController::class, 'store'])->name('users.store');

    // Get users from JSON data source
    $router->get('/json', [UserController::class, 'jsonUsers'])->name('users.json');

    // Search users from JSON data source
    $router->get('/search', [UserController::class, 'searchJson'])->name('users.search');

    // Show a single user
    $router->get('/{id}', [UserController::class, 'show'])
        ->where('id', '[0-9]+')  // using the where() method for constraints
        ->name('users.show');
});

// 404 fallback route
$router->any('{any}', function () {
    return view('errors/404', [
        'title' => 'Page Not Found',
    ], 404);
})->where('any', '.*');