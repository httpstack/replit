<?php

namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Container\Container;
use Framework\Exceptions\FrameworkException;
use Framework\Middleware\MiddlewareStack;

/**
 * Router Class
 * 
 * Handles HTTP routing with flexible routing patterns and dispatching
 */
class Router
{
    /**
     * The container instance
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * Array of registered routes
     * 
     * @var array
     */
    protected array $routes = [];
    
    /**
     * The current route being executed
     * 
     * @var Route|null
     */
    protected ?Route $currentRoute = null;
    
    /**
     * Global middleware applied to all routes
     * 
     * @var array
     */
    protected array $middleware = [];
    
    /**
     * Route groups configuration
     * 
     * @var array
     */
    protected array $groupStack = [];
    
    /**
     * Named routes registry
     * 
     * @var array
     */
    protected array $nameList = [];
    protected string $basePath = '/replit';
    /**
     * Create a new router instance
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Add a GET route
     * 
     * @param string $uri
     * @param mixed $handler
     * @return Route
     */
    public function get(string $uri, $handler): Route
    {
        echo "URI coming into Router:get: {$uri}\n";
        $rt = $this->addRoute(['GET'], $uri, $handler);
        /*
        echo "<pre>";
        echo "GET route added: {$uri}\n";
        echo "Handler: ";
        if (is_array($handler)) {
            echo implode('::', $handler);
        } elseif (is_string($handler)) {
            echo $handler;
        } elseif ($handler instanceof \Closure) {
            echo 'Closure';
        } else {
            echo gettype($handler);
        }
        echo "\n";
        echo "Route details:\n";
        echo "Methods: " . implode(', ', $rt->getMethods()) . "\n";
        echo "URI: " . $rt->getUri() . "\n";
        echo "</pre>";
        */
        return $rt;
    }
    
    /**
     * Add a POST route
     * 
     * @param string $uri
     * @param mixed $handler
     * @return Route
     */
    public function post(string $uri, $handler): Route
    {
        return $this->addRoute(['POST'], $uri, $handler);
    }
    
    /**
     * Add a PUT route
     * 
     * @param string $uri
     * @param mixed $handler
     * @return Route
     */
    public function put(string $uri, $handler): Route
    {
        return $this->addRoute(['PUT'], $uri, $handler);
    }
    
    /**
     * Add a PATCH route
     * 
     * @param string $uri
     * @param mixed $handler
     * @return Route
     */
    public function patch(string $uri, $handler): Route
    {
        return $this->addRoute(['PATCH'], $uri, $handler);
    }
    
    /**
     * Add a DELETE route
     * 
     * @param string $uri
     * @param mixed $handler
     * @return Route
     */
    public function delete(string $uri, $handler): Route
    {
        return $this->addRoute(['DELETE'], $uri, $handler);
    }
    
    /**
     * Add a route that responds to any HTTP method
     * 
     * @param string $uri
     * @param mixed $handler
     * @return Route
     */
    public function any(string $uri, $handler): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $handler);
    }
    
    /**
     * Add a route with custom HTTP methods
     * 
     * @param array $methods
     * @param string $uri
     * @param mixed $handler
     * @return Route
     */
    public function match(array $methods, string $uri, $handler): Route
    {
        return $this->addRoute($methods, $uri, $handler);
    }
    
    /**
     * Create a route group with shared attributes
     * 
     * @param array $attributes
     * @param callable $callback
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        
        $callback($this);
        
        array_pop($this->groupStack);
    }
    
    /**
     * Get the current group stack
     * 
     * @return array
     */
    protected function getGroupStack(): array
    {
        if (empty($this->groupStack)) {
            return [];
        }
        
        $merged = [];
        
        foreach ($this->groupStack as $group) {
            $merged = array_merge_recursive($merged, $group);
        }
        
        return $merged;
    }
    
    /**
     * Add route to the collection
     * 
     * @param array $methods
     * @param string $uri
     * @param mixed $handler
     * @return Route
     */
    protected function addRoute(array $methods, string $uri, $handler): Route
    {
        echo "URI coming into Router:addRoute {$uri}\n";
        // Get the current group stack
        $groupStack = $this->getGroupStack();
        
        // Prepend group prefix to URI if it exists
        if (isset($groupStack['prefix'])) {
            $uri = trim($groupStack['prefix'], '/') . '/' . trim($uri, '/');
            $uri = trim($uri, '/');
            // Ensure we always have a leading slash
            $uri = '/' . $uri;
        }
        
        // Create a new route instance
        $route = new Route($methods, $uri, $handler);
        
        // Add group middleware to the route if they exist
        if (isset($groupStack['middleware'])) {
            $middleware = is_array($groupStack['middleware']) 
                ? $groupStack['middleware'] 
                : [$groupStack['middleware']];
                
            $route->middleware($middleware);
        }
        
        // Store the route
        $this->routes[] = $route;
        
        return $route;
    }
    
    /**
     * Add global middleware to the router
     * 
     * @param string|array $middleware
     * @return $this
     */
    public function middleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        
        return $this;
    }
    
    /**
     * Add a named route to the registry
     * 
     * @param string $name
     * @param Route $route
     * @return void
     */
    public function addNamedRoute(string $name, Route $route): void
    {
        $this->nameList[$name] = $route;
    }
    
    /**
     * Generate a URL for a named route
     * 
     * @param string $name
     * @param array $parameters
     * @return string
     * @throws FrameworkException
     */
    public function url(string $name, array $parameters = []): string
    {
        if (!isset($this->nameList[$name])) {
            throw new FrameworkException("Route [{$name}] not defined.");
        }
        
        return $this->nameList[$name]->generateUrl($parameters);
    }
    
    /**
     * Dispatch the request to the appropriate route
     * 
     * @param Request $request
     * @return Response
     * @throws FrameworkException
     */
    public function dispatch(Request $request): Response
    {
        //var_dump($request);
        // Find a matching route
        $route = $this->findRoute($request);
        
        if ($route === null) {
            throw new FrameworkException("No route found for {$request->getMethod()} {$request->getPath()}", 404);
        }
        
        $this->currentRoute = $route;
        
        // Create a middleware stack with global middleware followed by route middleware
        $middlewareStack = new MiddlewareStack($this->container);
        
        // Add global middleware
        foreach ($this->middleware as $middleware) {
            $middlewareStack->add($middleware);
        }
        
        // Add route middleware
        foreach ($route->getMiddleware() as $middleware) {
            $middlewareStack->add($middleware);
        }
        
        // Execute the middleware stack, passing the route handler as the core handler
        return $middlewareStack->handle($request, function ($request) use ($route) {
            return $this->runRoute($route, $request);
        });
    }
    
    /**
     * Find a route that matches the request
     * 
     * @param Request $request
     * @return Route|null
     */
    protected function findRoute(Request $request): ?Route
    {
        $method = $request->getMethod();
        $path = $request->getUri();
        
        foreach ($this->routes as $route) {
            echo "stored route is :" . $route->getUri(); 
            echo "path is :" . $path;         
            if (!in_array($method, $route->getMethods())) {
                continue;
            }
            
            $matches = [];
            $pattern = $this->buildRegexPattern($route->getUri());
            $pattern = $this->basePath . $pattern;
            echo "pattern is : " . $pattern;
            echo "<br>";
            if (preg_match($pattern, $path, $matches)) {
                // Extract parameters from the URI
                $params = $this->extractParameters($route->getUri(), $matches);
                $route->setParameters($params);
                
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Build a regex pattern for a route URI
     * 
     * @param string $uri
     * @return string
     */
    protected function buildRegexPattern(string $uri): string
    {
        echo "<br>";
        echo "// Replace named parameters with regex patterns";
       $pattern = preg_replace('/{([a-zA-Z0-9_]+)}/', '(?<$1>[^/]+)', $uri);
       echo $pattern;
        
        echo "<br>";
        echo "// Replace optional parameters";
        $pattern = preg_replace('/{([a-zA-Z0-9_]+)\?}/', '(?<$1>[^/]*)?', $pattern);
        echo $pattern;
        
        
        echo "<br>";
        echo "// Escape forward slashes";
        $pattern = str_replace('/', '\/', $pattern);
        echo $pattern;

        return '/^' . $pattern . '$/';
    }
    
    /**
     * Extract named parameters from the URI
     * 
     * @param string $uri
     * @param array $matches
     * @return array
     */
    protected function extractParameters(string $uri, array $matches): array
    {
        $params = [];
        
        preg_match_all('/{([a-zA-Z0-9_]+)(\?)?}/', $uri, $paramNames);
        
        foreach ($paramNames[1] as $name) {
            if (isset($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }
        
        return $params;
    }
    
    /**
     * Run the route handler
     * 
     * @param Route $route
     * @param Request $request
     * @return Response
     * @throws FrameworkException
     */
    protected function runRoute(Route $route, Request $request): Response
    {
        $handler = $route->getHandler();
        $params = $route->getParameters();
        
        // Handle closure callbacks
        if ($handler instanceof \Closure) {
            $response = $this->container->call($handler, $params);
            return $this->prepareResponse($response);
        }
        
        // Handle controller@method syntax
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            return $this->runControllerAction($controller, $method, $params, $request);
        }
        
        // Handle Class::method syntax
        if (is_string($handler) && strpos($handler, '::') !== false) {
            list($class, $method) = explode('::', $handler);
            $response = $this->container->call([$class, $method], $params);
            return $this->prepareResponse($response);
        }
        
        // Handle invokable classes
        if (is_string($handler) && class_exists($handler)) {
            $instance = $this->container->make($handler);
            
            if (method_exists($instance, '__invoke')) {
                $response = $this->container->call([$instance, '__invoke'], $params);
                return $this->prepareResponse($response);
            }
        }
        
        // Handle array [Controller::class, 'method'] syntax
        if (is_array($handler) && count($handler) === 2) {
            $response = $this->container->call($handler, $params);
            return $this->prepareResponse($response);
        }
        
        throw new FrameworkException("Invalid route handler");
    }
    
    /**
     * Run a controller action
     * 
     * @param string $controller
     * @param string $method
     * @param array $params
     * @param Request $request
     * @return Response
     * @throws FrameworkException
     */
    protected function runControllerAction(
        string $controller, 
        string $method, 
        array $params, 
        Request $request
    ): Response {
        $controller = $this->container->make($controller);
        
        if (!method_exists($controller, $method)) {
            throw new FrameworkException("Method [{$method}] does not exist on controller");
        }
        
        $response = $this->container->call([$controller, $method], $params);
        
        return $this->prepareResponse($response);
    }
    
    /**
     * Prepare the response object
     * 
     * @param mixed $response
     * @return Response
     */
    protected function prepareResponse($response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }
        
        if (is_array($response) || is_object($response)) {
            return Response::json($response);
        }
        
        if (is_string($response)) {
            return Response::html($response);
        }
        
        return new Response((string) $response);
    }
    
    /**
     * Get the current route
     * 
     * @return Route|null
     */
    public function getCurrentRoute(): ?Route
    {
        return $this->currentRoute;
    }
    
    /**
     * Get all registered routes
     * 
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
