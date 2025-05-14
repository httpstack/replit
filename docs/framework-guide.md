# Custom PHP MVC Framework Guide

This guide shows you how to use our custom PHP MVC framework efficiently for building web applications.

## Table of Contents

1. [Directory Structure](#directory-structure)
2. [Routing](#routing)
3. [Controllers](#controllers)
4. [Views/Templates](#viewstemplates)
5. [Database Connectivity](#database-connectivity)
6. [JSON Data Sources](#json-data-sources)
7. [Middleware](#middleware)

## Directory Structure

```
├── app                    # Application-specific code
│   ├── Controllers        # Your controllers
│   ├── Middleware         # Your middleware
│   ├── Models             # Your models
│   └── Providers          # Service providers
├── config                 # Configuration files
├── public                 # Publicly accessible files
│   └── index.php          # Entry point
├── routes                 # Route definitions
│   └── web.php            # Web routes
├── src                    # Framework core
├── templates              # View templates
└── vendor                 # Composer dependencies
```

## Routing

Routes are defined in `routes/web.php`. The framework supports multiple ways to define routes:

### Basic Route with Closure

```php
// Define a route with a closure
$router->get('/', function () {
    return view('home/index', [
        'title' => 'Home Page',
        'message' => 'Welcome to our application'
    ]);
});
```

### Route with Controller

```php
// Using array syntax [Controller::class, 'method']
$router->get('/about', [HomeController::class, 'about']);

// Using string syntax 'Controller@method'
$router->get('/contact', 'App\\Controllers\\HomeController@contact');
```

### Route Parameters

```php
// Required parameter
$router->get('/users/{id}', [UserController::class, 'show']);

// Optional parameter
$router->get('/posts/{slug?}', [PostController::class, 'show']);

// Parameter constraints
$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+');
```

### Route Groups

```php
$router->group(['prefix' => 'admin', 'middleware' => [AdminMiddleware::class]], function (Router $router) {
    $router->get('/', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminController::class, 'users']);
});
```

### HTTP Methods

```php
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);
```

### Named Routes

```php
$router->get('/contact', [HomeController::class, 'contact'])->name('contact');
```

## Controllers

Controllers are stored in the `app/Controllers` directory and should extend the `BaseController`.

### Basic Controller Example

```php
<?php

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;

class HomeController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('home/index', [
            'title' => 'Home Page',
            'message' => 'Welcome to our application'
        ]);
    }
    
    public function about(Request $request): Response
    {
        return $this->view('home/about', [
            'title' => 'About Us'
        ]);
    }
}
```

### Request Object

The `Request` object provides methods to access input data:

```php
// Get a query parameter (?q=search)
$query = $request->getQuery('q', 'default value');

// Get a post parameter
$email = $request->getPost('email');

// Get all post data
$data = $request->getPost();

// Check if a parameter exists
if ($request->has('id')) {
    // Do something
}

// Get only specific parameters
$credentials = $request->only(['email', 'password']);

// Get all except specific parameters
$data = $request->except(['_token', '_method']);
```

### Response Methods

The `BaseController` provides several response methods:

```php
// Return a view
return $this->view('users/show', ['user' => $user]);

// Return a JSON response
return $this->json(['status' => 'success', 'data' => $user]);

// Redirect to another URL
return $this->redirect('/users');

// Return a 404 response
return $this->view('errors/404', ['message' => 'Not found'], 404);
```

## Views/Templates

Templates are stored in the `templates` directory and use standard HTML with the `{{ key }}` syntax for variable substitution.

### Basic Template

```html
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ title }}</h1>
    <p>{{ message }}</p>
    
    <ul>
        {% for item in items %}
            <li>{{ item }}</li>
        {% endfor %}
    </ul>
</body>
</html>
```

### Rendering a View

```php
return $this->view('home/index', [
    'title' => 'Home Page',
    'message' => 'Welcome to our application',
    'items' => ['Item 1', 'Item 2', 'Item 3']
]);
```

## Database Connectivity

The framework provides a `DatabaseConnection` class for database operations.

### Setting Up a Database Connection

```php
use Framework\Database\DatabaseConnection;

$db = new DatabaseConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'my_app',
    'username' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4'
]);
```

### Executing Queries

```php
// Select multiple rows
$users = $db->select("SELECT * FROM users WHERE active = ?", [1]);

// Select a single row
$user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$id]);

// Insert data and get the ID
$userId = $db->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'created_at' => date('Y-m-d H:i:s')
]);

// Update data
$affected = $db->update('users', 
    ['name' => 'Jane Doe', 'updated_at' => date('Y-m-d H:i:s')],
    'id = ?',
    [$id]
);

// Delete data
$affected = $db->delete('users', 'id = ?', [$id]);

// Transactions
$db->beginTransaction();
try {
    // Execute multiple queries...
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    throw $e;
}
```

## JSON Data Sources

The framework provides a `JsonDataSource` class for working with JSON data from files or APIs.

### Loading JSON Data

```php
use Framework\Data\JsonDataSource;

// From a file
$users = new JsonDataSource('data/users.json');

// From an array
$data = new JsonDataSource([
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane']
]);

// From a URL (API)
$posts = new JsonDataSource('https://jsonplaceholder.typicode.com/posts');
```

### Working with JSON Data

```php
// Get all data
$allUsers = $users->getData();

// Get a specific value
$admin = $users->get('admin');

// Set a value
$users->set('active', true);

// Find an item
$user = $users->find('id', 5);

// Find all matching items
$admins = $users->findAll('role', 'admin');

// Filter data
$activeUsers = $users->filter(function($user) {
    return $user['active'] === true;
});

// Map data
$names = $users->map(function($user) {
    return $user['name'];
});

// Save changes
$users->save();  // Save to the original source
$users->save('data/users-backup.json');  // Save to a new file
```

## Middleware

Middleware can be used to filter HTTP requests entering your application.

### Creating Middleware

```php
<?php

namespace App\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            // Redirect to login page
            return redirect('/login');
        }
        
        // Call the next middleware or route handler
        return $next($request);
    }
}
```

### Applying Middleware

```php
// Apply to a single route
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(AuthMiddleware::class);

// Apply to a group of routes
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->get('/profile', [ProfileController::class, 'index']);
});
```

## Complete Example

Here's a complete example showing how to create routes, controllers, and templates for a user management system:

### Route Definition (routes/web.php)

```php
// User routes
$router->group(['prefix' => 'users'], function ($router) {
    // List all users
    $router->get('/', [UserController::class, 'index'])->name('users.index');
    
    // Show the create user form
    $router->get('/create', [UserController::class, 'create'])->name('users.create');
    
    // Store a new user
    $router->post('/', [UserController::class, 'store'])->name('users.store');
    
    // Show a single user
    $router->get('/{id}', [UserController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('users.show');
    
    // Show the edit form
    $router->get('/{id}/edit', [UserController::class, 'edit'])
        ->where('id', '[0-9]+')
        ->name('users.edit');
    
    // Update a user
    $router->put('/{id}', [UserController::class, 'update'])
        ->where('id', '[0-9]+')
        ->name('users.update');
    
    // Delete a user
    $router->delete('/{id}', [UserController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('users.destroy');
});
```

### Controller (app/Controllers/UserController.php)

```php
<?php

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Database\DatabaseConnection;

class UserController extends BaseController
{
    protected DatabaseConnection $db;
    
    public function __construct()
    {
        $this->db = new DatabaseConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'my_app',
            'username' => 'root',
            'password' => '',
        ]);
    }
    
    public function index(Request $request): Response
    {
        $users = $this->db->select("SELECT * FROM users ORDER BY created_at DESC");
        
        return $this->view('users/index', [
            'users' => $users,
            'title' => 'User List',
        ]);
    }
    
    public function create(Request $request): Response
    {
        return $this->view('users/create', [
            'title' => 'Create User',
        ]);
    }
    
    public function store(Request $request): Response
    {
        $data = $request->getPost();
        
        // Validate
        $errors = $this->validateUser($data);
        
        if (!empty($errors)) {
            return $this->view('users/create', [
                'errors' => $errors,
                'data' => $data,
                'title' => 'Create User',
            ]);
        }
        
        // Insert
        $userId = $this->db->insert('users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        return $this->redirect("/users/{$userId}");
    }
    
    public function show(Request $request, int $id): Response
    {
        $user = $this->db->selectOne("SELECT * FROM users WHERE id = ?", [$id]);
        
        if (!$user) {
            return $this->view('errors/404', [
                'title' => 'User Not Found',
            ], 404);
        }
        
        return $this->view('users/show', [
            'user' => $user,
            'title' => "User: {$user['name']}",
        ]);
    }
    
    protected function validateUser(array $data): array
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        return $errors;
    }
}
```

This guide should help you understand how to use our custom PHP MVC framework to build web applications efficiently.