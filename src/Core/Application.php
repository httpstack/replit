<?php

namespace Framework\Core;

use Framework\Container\Container;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Routing\Router;
use Framework\Container\ServiceProvider;
use Framework\Exceptions\FrameworkException;

/**
 * Application Class
 * 
 * The core application class that bootstraps and runs the application
 */
class Application
{
    /**
     * The container instance
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * The base path of the application
     * 
     * @var string
     */
    protected string $basePath;
    
    /**
     * Application environment
     * 
     * @var string
     */
    protected string $environment = 'production';
    
    /**
     * Whether the application is in debug mode
     * 
     * @var bool
     */
    protected bool $debug = false;
    
    /**
     * Service providers
     * 
     * @var array
     */
    protected array $serviceProviders = [];
    
    /**
     * Booted service providers
     * 
     * @var array
     */
    protected array $bootedProviders = [];
    
    /**
     * Create a new application instance
     * 
     * @param string $basePath
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
        $this->container = new Container();
        
        // Register the application in the container
        $this->container->instance('app', $this);
        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);
        
        // Make the app globally available
        $GLOBALS['app'] = $this;
    }
    
    /**
     * Get the application container
     * 
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Get the application base path
     * 
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Get the configuration path
     * 
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Get the routes path
     * 
     * @param string $path
     * @return string
     */
    public function routesPath(string $path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'routes' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Get the app path
     * 
     * @param string $path
     * @return string
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Get the templates path
     * 
     * @param string $path
     * @return string
     */
    public function templatesPath(string $path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'templates' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    /**
     * Load a configuration file
     * 
     * @param string $name
     * @return array
     */
    public function config(string $name): array
    {
        $path = $this->configPath($name . '.php');
        
        if (file_exists($path)) {
            return require $path;
        }
        
        return [];
    }
    
    /**
     * Set the application environment
     * 
     * @param string $environment
     * @return $this
     */
    public function setEnvironment(string $environment): self
    {
        $this->environment = $environment;
        return $this;
    }
    
    /**
     * Get the application environment
     * 
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }
    
    /**
     * Determine if the application is in the given environment
     * 
     * @param string $environment
     * @return bool
     */
    public function isEnvironment(string $environment): bool
    {
        return $this->environment === $environment;
    }
    
    /**
     * Enable or disable debug mode
     * 
     * @param bool $debug
     * @return $this
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }
    
    /**
     * Determine if the application is in debug mode
     * 
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }
    
    /**
     * Register a service provider
     * 
     * @param string $provider
     * @return $this
     */
    public function register(string $provider): self
    {
        if (isset($this->serviceProviders[$provider])) {
            return $this;
        }
        
        $providerInstance = new $provider($this->container);
        
        if (!($providerInstance instanceof ServiceProvider)) {
            throw new FrameworkException("{$provider} must be an instance of ServiceProvider");
        }
        
        $this->serviceProviders[$provider] = $providerInstance;
        
        $providerInstance->register();
        
        return $this;
    }
    
    /**
     * Boot the service providers
     * 
     * @return $this
     */
    public function boot(): self
    {
        foreach ($this->serviceProviders as $provider) {
            if (!isset($this->bootedProviders[get_class($provider)])) {
                $provider->boot();
                $this->bootedProviders[get_class($provider)] = true;
            }
        }
        
        return $this;
    }
    
    /**
     * Register all service providers from the config
     * 
     * @return $this
     */
    public function registerProviders(): self
    {
        $providers = $this->config('app')['providers'] ?? [];
        
        foreach ($providers as $provider) {
            $this->register($provider);
        }
        
        return $this;
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->container->make($name);
    }
    
    /**
     * Check if a service exists in the container
     * 
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->container->has($name);
    }
    
    /**
     * Register a binding with the container
     * 
     * @param string $abstract
     * @param callable|string|null $concrete
     * @param bool $shared
     * @return $this
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): self
    {
        $this->container->bind($abstract, $concrete, $shared);
        return $this;
    }
    
    /**
     * Register a shared binding with the container
     * 
     * @param string $abstract
     * @param callable|string|null $concrete
     * @return $this
     */
    public function singleton(string $abstract, $concrete = null): self
    {
        $this->container->singleton($abstract, $concrete);
        return $this;
    }
    
    /**
     * Register an existing instance with the container
     * 
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance(string $abstract, $instance)
    {
        return $this->container->instance($abstract, $instance);
    }
    
    /**
     * Run the application
     * 
     * @return void
     */
    public function run(): void
    {
        try {
            // Create the request from globals
            $request = Request::capture();
            /*
            echo "<pre>";
            echo "The URI returned by the captured request Request:getMethod" . $request->getUri() . "\n";
            echo "Request Method: " . $request->getMethod() . "\n";
            echo "Request Headers: \n";
            foreach ($request->getHeaders() as $key => $value) {
                echo "$key: $value\n";
            }
            echo "Request Body: \n";
            echo $request->getBody() . "\n";
            echo "</pre>";
            */
            $this->instance('request', $request);
            
            // Get the router
            $router = $this->get('router');
            
            // Dispatch the request to the router
            $response = $router->dispatch($request);
            
            // Send the response
            $response->send();
        } catch (FrameworkException $e) {
            $this->handleException($e);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Handle an exception
     * 
     * @param \Exception $e
     * @return void
     */
    public function handleException(\Exception $e): void
    {
        $statusCode = $e instanceof FrameworkException ? $e->getCode() : 500;
        
        if (!$statusCode || $statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }
        
        // If in debug mode, show the error details
        if ($this->debug) {
            $response = new Response(
                '<h1>Error: ' . $e->getMessage() . '</h1>' .
                '<p>File: ' . $e->getFile() . ' (Line: ' . $e->getLine() . ')</p>' .
                '<pre>' . $e->getTraceAsString() . '</pre>',
                $statusCode,
                ['Content-Type' => 'text/html']
            );
        } else {
            // In production, show a generic error
            $response = new Response(
                '<h1>Server Error</h1>' .
                '<p>Sorry, something went wrong on our servers.</p>',
                $statusCode,
                ['Content-Type' => 'text/html']
            );
        }
        
        $response->send();
    }
}
