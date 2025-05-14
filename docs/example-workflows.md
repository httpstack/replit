# Framework Example Workflows

This document provides concrete examples of common workflows in our custom PHP MVC framework.

## 1. Setting Up Routes

The routing system allows you to define how your application responds to client requests. Routes are defined in the `routes/web.php` file.

### Basic Routing Examples

```php
<?php
// routes/web.php

use App\Controllers\HomeController;
use App\Controllers\UserController;
use Framework\Routing\Router;

// Get the router instance
$router = app('router');

// Simple route with a closure
$router->get('/', function() {
    return view('home/index', [
        'title' => 'Home Page'
    ]);
});

// Route to a controller method
$router->get('/about', [HomeController::class, 'about']);

// Route with a parameter
$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+'); // Parameter constraint

// Route group for related routes
$router->group(['prefix' => 'admin'], function(Router $router) {
    $router->get('/', function() {
        return view('admin/dashboard');
    });
    $router->get('/users', [AdminController::class, 'users']);
});

// Routes for a REST resource
$router->get('/posts', [PostController::class, 'index']);
$router->get('/posts/create', [PostController::class, 'create']);
$router->post('/posts', [PostController::class, 'store']);
$router->get('/posts/{id}', [PostController::class, 'show']);
$router->get('/posts/{id}/edit', [PostController::class, 'edit']);
$router->put('/posts/{id}', [PostController::class, 'update']);
$router->delete('/posts/{id}', [PostController::class, 'destroy']);
```

## 2. Creating Controllers

Controllers handle the incoming requests and return responses. They are stored in the `app/Controllers` directory.

### Basic Controller Example

```php
<?php
// app/Controllers/ProductController.php

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;

class ProductController extends BaseController
{
    public function index(Request $request): Response
    {
        // Get query parameters
        $category = $request->getQuery('category');
        $sort = $request->getQuery('sort', 'name');
        
        // Load data (this would typically come from a database)
        $products = [
            ['id' => 1, 'name' => 'Product A', 'price' => 19.99],
            ['id' => 2, 'name' => 'Product B', 'price' => 29.99],
            ['id' => 3, 'name' => 'Product C', 'price' => 39.99],
        ];
        
        // Return a view with data
        return $this->view('products/index', [
            'title' => 'Products',
            'products' => $products,
            'category' => $category,
            'sort' => $sort
        ]);
    }
    
    public function show(Request $request, int $id): Response
    {
        // In a real app, you'd fetch this from the database
        $product = [
            'id' => $id,
            'name' => 'Product ' . $id,
            'price' => 19.99 + ($id * 10),
            'description' => 'This is product ' . $id
        ];
        
        return $this->view('products/show', [
            'title' => $product['name'],
            'product' => $product
        ]);
    }
    
    public function create(Request $request): Response
    {
        return $this->view('products/create', [
            'title' => 'Create Product'
        ]);
    }
    
    public function store(Request $request): Response
    {
        // Get all POST data
        $data = $request->getPost();
        
        // Validate
        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        if (!is_numeric($data['price'])) {
            $errors['price'] = 'Price must be a number';
        }
        
        if (!empty($errors)) {
            return $this->view('products/create', [
                'title' => 'Create Product',
                'errors' => $errors,
                'data' => $data
            ]);
        }
        
        // In a real app, save to database and get the ID
        $id = 123;
        
        // Redirect to the new product
        return $this->redirect("/products/{$id}");
    }
}
```

## 3. MySQL Database Connectivity

The framework provides a `DatabaseConnection` class for working with MySQL databases.

### Database Usage Example

```php
<?php
// Example database-connected controller

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Database\DatabaseConnection;

class OrderController extends BaseController
{
    protected DatabaseConnection $db;
    
    public function __construct()
    {
        // Set up the database connection
        $this->db = new DatabaseConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'my_store',
            'username' => 'root',
            'password' => 'secret',
            'charset' => 'utf8mb4'
        ]);
    }
    
    public function index(Request $request): Response
    {
        // Get all orders with their associated customer
        $orders = $this->db->select("
            SELECT o.*, c.name as customer_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            ORDER BY o.created_at DESC
            LIMIT 20
        ");
        
        return $this->view('orders/index', [
            'title' => 'Recent Orders',
            'orders' => $orders
        ]);
    }
    
    public function show(Request $request, int $id): Response
    {
        // Get the order
        $order = $this->db->selectOne("
            SELECT o.*, c.name as customer_name, c.email as customer_email
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?
        ", [$id]);
        
        if (!$order) {
            return $this->view('errors/404', [
                'title' => 'Order Not Found'
            ], 404);
        }
        
        // Get the order items
        $items = $this->db->select("
            SELECT oi.*, p.name as product_name, p.sku
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ", [$id]);
        
        return $this->view('orders/show', [
            'title' => "Order #{$order['id']}",
            'order' => $order,
            'items' => $items
        ]);
    }
    
    public function store(Request $request): Response
    {
        $data = $request->getPost();
        
        // Start transaction to ensure all operations succeed or fail together
        $this->db->beginTransaction();
        
        try {
            // Insert order
            $orderId = $this->db->insert('orders', [
                'customer_id' => $data['customer_id'],
                'total' => $data['total'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Insert order items
            foreach ($data['items'] as $item) {
                $this->db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
                
                // Update inventory
                $this->db->execute(
                    "UPDATE products SET stock = stock - ? WHERE id = ?",
                    [$item['quantity'], $item['product_id']]
                );
            }
            
            // Commit transaction
            $this->db->commit();
            
            return $this->redirect("/orders/{$orderId}");
        } catch (\Exception $e) {
            // Rollback on error
            $this->db->rollBack();
            
            return $this->view('orders/create', [
                'title' => 'Create Order',
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }
}
```

## 4. Working with JSON Data Sources

The framework provides a `JsonDataSource` class for working with JSON data.

### JSON Data Source Example

```php
<?php
// Example controller working with JSON data

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Data\JsonDataSource;

class ConfigController extends BaseController
{
    protected JsonDataSource $config;
    
    public function __construct()
    {
        // Load configuration from a JSON file
        $this->config = new JsonDataSource('config/app_settings.json');
    }
    
    public function index(Request $request): Response
    {
        // Get all configuration data
        $settings = $this->config->getData();
        
        return $this->view('config/index', [
            'title' => 'Application Settings',
            'settings' => $settings
        ]);
    }
    
    public function update(Request $request): Response
    {
        $data = $request->getPost();
        
        // Update settings
        foreach ($data as $key => $value) {
            $this->config->set($key, $value);
        }
        
        // Save changes back to the file
        $this->config->save();
        
        return $this->redirect('/config');
    }
    
    public function exportSettings(Request $request): Response
    {
        // Get specific settings for export
        $exportSettings = $this->config->filter(function($value, $key) {
            // Exclude sensitive settings
            return !in_array($key, ['api_keys', 'credentials', 'secrets']);
        });
        
        // Return as a JSON response
        return $this->json($exportSettings);
    }
}
```

### Working with External APIs

```php
<?php
// Example controller fetching data from an external API

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Data\JsonDataSource;

class WeatherController extends BaseController
{
    public function index(Request $request): Response
    {
        $city = $request->getQuery('city', 'New York');
        
        try {
            // Fetch weather data from an API
            $apiUrl = "https://api.example.com/weather?city=" . urlencode($city) . "&apikey=YOUR_API_KEY";
            $weatherData = new JsonDataSource($apiUrl);
            
            return $this->view('weather/index', [
                'title' => "Weather for {$city}",
                'weather' => $weatherData->getData(),
                'city' => $city
            ]);
        } catch (\Exception $e) {
            return $this->view('weather/index', [
                'title' => "Weather for {$city}",
                'error' => "Failed to fetch weather data: {$e->getMessage()}",
                'city' => $city
            ]);
        }
    }
    
    public function forecast(Request $request, string $city): Response
    {
        try {
            // Fetch 5-day forecast
            $apiUrl = "https://api.example.com/forecast?city=" . urlencode($city) . "&days=5&apikey=YOUR_API_KEY";
            $forecastData = new JsonDataSource($apiUrl);
            
            // Process the data
            $forecast = $forecastData->getData();
            
            // Find the highest and lowest temperatures
            $temperatures = $forecastData->map(function($day) {
                return $day['temperature'];
            });
            
            $highTemp = max($temperatures);
            $lowTemp = min($temperatures);
            
            return $this->view('weather/forecast', [
                'title' => "5-Day Forecast for {$city}",
                'forecast' => $forecast,
                'city' => $city,
                'highTemp' => $highTemp,
                'lowTemp' => $lowTemp
            ]);
        } catch (\Exception $e) {
            return $this->view('weather/forecast', [
                'title' => "5-Day Forecast for {$city}",
                'error' => "Failed to fetch forecast data: {$e->getMessage()}",
                'city' => $city
            ]);
        }
    }
}
```

## 5. Complete Application Flow Example

Here's a complete example showing how a request flows through the framework:

1. User visits `/products?category=electronics`
2. Request goes through `public/index.php` entry point
3. The Bootstrap class initializes the application
4. The Router matches the URL to a route
5. The matching route calls the ProductController's index method
6. The controller processes the request:
   - Gets the category parameter
   - Fetches products from the database
   - Renders the view with the data
7. The response is sent back to the user

### Example Template

```html
<!-- templates/products/index.html -->
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <header>
        <h1>{{ title }}</h1>
        <nav>
            <a href="/">Home</a>
            <a href="/products">Products</a>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
        </nav>
    </header>
    
    <main>
        <div class="filters">
            <form action="/products" method="get">
                <label for="category">Category:</label>
                <select name="category" id="category">
                    <option value="">All Categories</option>
                    <option value="electronics" {{ category == 'electronics' ? 'selected' : '' }}>Electronics</option>
                    <option value="clothing" {{ category == 'clothing' ? 'selected' : '' }}>Clothing</option>
                    <option value="books" {{ category == 'books' ? 'selected' : '' }}>Books</option>
                </select>
                
                <label for="sort">Sort By:</label>
                <select name="sort" id="sort">
                    <option value="name" {{ sort == 'name' ? 'selected' : '' }}>Name</option>
                    <option value="price_low" {{ sort == 'price_low' ? 'selected' : '' }}>Price (Low to High)</option>
                    <option value="price_high" {{ sort == 'price_high' ? 'selected' : '' }}>Price (High to Low)</option>
                </select>
                
                <button type="submit">Apply</button>
            </form>
        </div>
        
        <div class="products">
            {% if products|length > 0 %}
                {% for product in products %}
                    <div class="product-card">
                        <h2>{{ product.name }}</h2>
                        <p class="price">${{ product.price }}</p>
                        <a href="/products/{{ product.id }}" class="button">View Details</a>
                    </div>
                {% endfor %}
            {% else %}
                <p class="no-results">No products found matching your criteria.</p>
            {% endif %}
        </div>
    </main>
    
    <footer>
        <p>&copy; {{ 'now'|date('Y') }} Our Store. All rights reserved.</p>
    </footer>
</body>
</html>
```

This provides a comprehensive guide to creating common workflows in our custom PHP MVC framework.