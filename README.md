# Custom PHP MVC Framework

A lightweight, flexible, and modern PHP MVC framework designed for building web applications with a clean separation of concerns.

## Key Features

* **MVC Architecture**: Clean separation of models, views, and controllers
* **Service Container**: Powerful dependency injection container
* **Advanced Routing**: Support for RESTful routes, parameter constraints, and route groups  
* **Middleware Support**: Filter HTTP requests with middleware
* **DOM-based Templating**: Advanced template manipulation with DOM
* **Flexible File Loading**: Efficient file management system
* **Data Binding**: Easily bind data to views with `{{ key }}` syntax
* **Database Integration**: Fluent interface for MySQL databases
* **JSON Data Sources**: Work with JSON files and APIs

## Getting Started

First, clone this repository to your local machine:

```bash
git clone https://github.com/yourusername/framework.git
cd framework
```

### Requirements

* PHP 8.0 or higher
* Composer 

### Installation

1. Install required dependencies:

```bash
composer install
```

2. Start the PHP development server:

```bash
php -S localhost:8000 -t public
```

3. Visit http://localhost:8000 in your browser

## Documentation

For detailed instructions and examples, please refer to the documentation:

- [Framework Guide](docs/framework-guide.md) - Core concepts and API documentation
- [Example Workflows](docs/example-workflows.md) - Common usage patterns and examples

## Directory Structure

```
├── app                    # Application-specific code
│   ├── Controllers        # Your controllers
│   ├── Middleware         # Your middleware
│   ├── Models             # Your models
│   └── Providers          # Service providers
├── config                 # Configuration files
├── data                   # Data files (JSON, etc.)
├── docs                   # Documentation
├── public                 # Publicly accessible files
│   └── index.php          # Entry point
├── routes                 # Route definitions
│   └── web.php            # Web routes
├── src                    # Framework core
├── templates              # View templates
└── vendor                 # Composer dependencies
```

## Example Usage

### Defining Routes

```php
// routes/web.php
$router->get('/', function () {
    return view('home/index', [
        'title' => 'Home Page',
        'message' => 'Welcome to our custom framework!'
    ]);
});

$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+');

$router->group(['prefix' => 'admin'], function ($router) {
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
});
```

### Creating a Controller

```php
// app/Controllers/UserController.php
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
            'password' => ''
        ]);
    }
    
    public function show(Request $request, int $id): Response
    {
        $user = $this->db->selectOne(
            "SELECT * FROM users WHERE id = ?", 
            [$id]
        );
        
        if (!$user) {
            return $this->view('errors/404', [
                'title' => 'User Not Found'
            ], 404);
        }
        
        return $this->view('users/show', [
            'title' => 'User Profile',
            'user' => $user
        ]);
    }
}
```

### View Template

```html
<!-- templates/users/show.html -->
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ title }}</h1>
    
    <div class="user-profile">
        <p><strong>ID:</strong> {{ user.id }}</p>
        <p><strong>Name:</strong> {{ user.name }}</p>
        <p><strong>Email:</strong> {{ user.email }}</p>
    </div>
    
    <a href="/users">Back to Users</a>
</body>
</html>
```

## License

This project is licensed under the MIT License.