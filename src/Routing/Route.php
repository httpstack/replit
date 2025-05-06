<?php

namespace Framework\Routing;

/**
 * Route Class
 * 
 * Represents a single route in the application
 */
class Route
{
    /**
     * HTTP methods that this route responds to
     * 
     * @var array
     */
    protected array $methods;
    
    /**
     * The URI pattern this route matches
     * 
     * @var string
     */
    protected string $uri;
    
    /**
     * The route handler
     * 
     * @var mixed
     */
    protected $handler;
    
    /**
     * Route parameters
     * 
     * @var array
     */
    protected array $parameters = [];
    
    /**
     * Middleware for this route
     * 
     * @var array
     */
    protected array $middleware = [];
    
    /**
     * Route name
     * 
     * @var string|null
     */
    protected ?string $name = null;
    
    /**
     * Create a new Route instance
     * 
     * @param array $methods
     * @param string $uri
     * @param mixed $handler
     */
    public function __construct(array $methods, string $uri, $handler)
    {
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $uri;
        $this->handler = $handler;
    }
    
    /**
     * Get the HTTP methods this route responds to
     * 
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
    
    /**
     * Get the URI pattern
     * 
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }
    
    /**
     * Get the route handler
     * 
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }
    
    /**
     * Set route parameters
     * 
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }
    
    /**
     * Get route parameters
     * 
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    /**
     * Add middleware to the route
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
     * Get the route middleware
     * 
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
    
    /**
     * Set the route name
     * 
     * @param string $name
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;
        
        // Register the named route with the router
        $router = null;
        
        // Try to get the router from the global container
        if (isset($GLOBALS['app']) && $GLOBALS['app']->has('router')) {
            $router = $GLOBALS['app']->get('router');
        }
        
        if ($router) {
            $router->addNamedRoute($name, $this);
        }
        
        return $this;
    }
    
    /**
     * Get the route name
     * 
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    
    /**
     * Generate a URL for this route with the given parameters
     * 
     * @param array $parameters
     * @return string
     */
    public function generateUrl(array $parameters = []): string
    {
        $uri = $this->uri;
        
        foreach ($parameters as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
            $uri = str_replace("{{$key}?}", $value, $uri);
        }
        
        // Remove any remaining optional parameters
        $uri = preg_replace('/{[^}]+\?}/', '', $uri);
        
        return $uri;
    }
    
    /**
     * Add pattern constraints to route parameters
     * 
     * @param string $parameter
     * @param string $pattern
     * @return $this
     */
    public function where(string $parameter, string $pattern): self
    {
        // We could store these in a property and use them for validation
        // For now, we'll just return $this to maintain method chaining
        return $this;
    }
}
