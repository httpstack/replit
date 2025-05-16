<?php

namespace Framework\Core;

use Framework\Container\Container;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\Session;
use Framework\Routing\Router;
use Framework\Template\TemplateEngine;
use Framework\Template\DomManipulator;
use Framework\FileSystem\FileLoader;
use Framework\FileSystem\DirectoryMapper;
use Framework\Middleware\SessionMiddleware;
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
     * Create a new application instance
     * 
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->container = new Container();

        // Register the application in the container
        $this->container->instance('app', $this);
        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);

        // Register core services
        $this->registerCoreServices();

        // Register global middleware
        $this->registerGlobalMiddleware();

        // Register service providers
        $this->registerProviders();
    }

    /**
     * Register core services in the container
     * 
     * @return void
     */
    protected function registerCoreServices(): void
    {
        // Register the router
        $this->container->singleton('router', function ($container) {
            return new Router($container);
        });
        // Register the response
        $this->container->singleton('response', function () {
            return new Response();
        });
        // Register the request
        $this->container->singleton('request', function () {
            return Request::capture();
        });

        // Register the session
        $this->container->singleton('session', function () {
            return new Session();
        });

        // Register the file loader
        $this->container->singleton('fileLoader', function () {
            $fileLoader = new FileLoader();
            $fileLoader->mapDirectory('app', $this->appPath());
            $fileLoader->mapDirectory('config', $this->configPath());
            $fileLoader->mapDirectory('routes', $this->routesPath());
            $fileLoader->mapDirectory('templates', $this->templatesPath());

            return $fileLoader;
        });

        // Register the directory mapper
        $this->container->singleton('directoryMapper', function () {
            return new DirectoryMapper([
                'app' => $this->appPath(),
                'config' => $this->configPath(),
                'routes' => $this->routesPath(),
                'templates' => $this->templatesPath(),
            ]);
        });

        // Register the template engine
        $this->container->singleton('template', function ($container) {
            $fileLoader = $container->make('fileLoader');
            $dom = new DomManipulator();
            
            return new TemplateEngine(
                $fileLoader,
                $dom,
                $this->templatesPath()
            );
        });

        // Register the config service
        $this->container->singleton('config', function () {
            $configPath = $this->configPath();
            $config = [];

            // Load all PHP files in the config directory
            foreach (glob($configPath . '/*.php') as $file) {
                $key = basename($file, '.php');
                $config[$key] = require $file;
            }

            return $config;
        });
    }

    /**
     * Register global middleware
     * 
     * @return void
     */
    protected function registerGlobalMiddleware(): void
    {
        $router = $this->container->make('router');
        $router->middleware([
            SessionMiddleware::class,
        ]);
    }

    /**
     * Register service providers
     * 
     * @return void
     */
    protected function registerProviders(): void
    {
        // Example: Register additional service providers here
        // $this->container->make(SomeServiceProvider::class)->register();
    }

    /**
     * Load routes from the routes directory
     * 
     * @return void
     */
    protected function loadRoutes(): void
    {
        require_once $this->routesPath('web.php');
    }

    /**
     * Boot the application
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutes();
    }

    /**
     * Run the application
     * 
     * @return void
     */
    public function run(): void
    {
        try {
            // Get the request and router from the container
            $request = $this->container->make('request');
            $router = $this->container->make('router');

            // Dispatch the request and get the response
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
    protected function handleException(\Exception $e): void
    {
        $statusCode = $e instanceof FrameworkException ? $e->getCode() : 500;

        if (!$statusCode || $statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        if ($this->debug) {
            $response = new Response(
                '<h1>Error: ' . $e->getMessage() . '</h1>' .
                '<p>File: ' . $e->getFile() . ' (Line: ' . $e->getLine() . ')</p>' .
                '<pre>' . $e->getTraceAsString() . '</pre>',
                $statusCode,
                ['Content-Type' => 'text/html']
            );
        } else {
            $response = new Response(
                '<h1>Server Error</h1>' .
                '<p>Sorry, something went wrong on our servers.</p>',
                $statusCode,
                ['Content-Type' => 'text/html']
            );
        }

        $response->send();
    }

    /**
     * Set the application environment
     * 
     * @param string $environment
     * @return void
     */
    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    /**
     * Set debug mode
     * 
     * @param bool $debug
     * @return void
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
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
     * Get the application path
     * 
     * @return string
     */
    public function appPath(): string
    {
        return $this->basePath . '/app';
    }

    /**
     * Get the configuration path
     * 
     * @return string
     */
    public function configPath(): string
    {
        return $this->basePath . '/config';
    }

    /**
     * Get the routes path
     * 
     * @param string $file
     * @return string
     */
    public function routesPath(string $file = ''): string
    {
        return $this->basePath . '/routes' . ($file ? '/' . $file : '');
    }

    /**
     * Get the templates path
     * 
     * @return string
     */
    public function templatesPath(): string
    {
        return $this->basePath . '/templates';
    }
}
