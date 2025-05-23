<?php

namespace Framework\Core;

use App\Models\TemplateModel;
use Framework\Container\Container;
use Framework\Database\DatabaseConnection;
use Framework\Exceptions\FrameworkException;
use Framework\FileSystem\DirectoryMapper;
use Framework\FileSystem\FileLoader;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\Session;
use Framework\Middleware\SessionMiddleware;
use Framework\Routing\Router;
use Framework\Template\TemplateEngine;

/**
 * Application Class.
 *
 * The core application class that bootstraps and runs the application
 */
class Application
{
    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * The base path of the application.
     */
    protected string $basePath;

    /**
     * Application environment.
     */
    protected string $environment = 'production';

    /**
     * Whether the application is in debug mode.
     */
    protected bool $debug = true;
    protected array $paths;
    /**
     * Create a new application instance.
     */
    public function __construct(string $configPath, bool $debug = true)
    {        
        $this->showErrors($this->debug);
        //REGISTER CONTAINER AND APP WITH THE CONTAINER
        $this->container = new Container();
        $this->container->instance(Container::class, $this->container);
        $this->container->instance('app', $this);
        $this->container->instance(self::class, $this);

        //BIND config OPERATION TO CONTAINER
        //AND REGISTER IT GLOBALLY SO THE INSTANCE CAN SEE IT
        $this->bindConfig($configPath);
        $this->setGlobal();

        
        $this->paths = $this->container->make('config')['app']['paths'];

        $this->registerCoreServices();

        // Register global middleware
        $this->registerGlobalMiddleware();

        // Register service providers
        $this->registerProviders();
        $this->registerTemplateServices();
        $this->setGlobal();
    }    //debug($this->paths);
    protected function bindConfig(string $configPath): void
    {
        // Register the config service
        $this->container->singleton('config', function () use ($configPath) {
            
            $config = [];

            // Load all PHP files in the config directory
            foreach (glob($configPath.'/*.php') as $file) {
                $key = basename($file, '.php');
                $config[$key] = require $file;
            }

            return $config;
        });
    }

    
    /**
     * Normalize a filesystem path: resolves .., ., duplicate slashes, and trims trailing slash (except root)
     */
    public static function normalizePath(string $path): string
    {
        // Replace backslashes with forward slashes
        $path = str_replace('\\', '/', $path);
        // Replace multiple slashes with a single slash
        $path = preg_replace('#/+#', '/', $path);
        // Resolve .. and .
        $parts = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') continue;
            if ($segment === '..') {
                array_pop($parts);
            } else {
                $parts[] = $segment;
            }
        }
        $normalized = (substr($path, 0, 1) === '/' ? '/' : '') . implode('/', $parts);
        // Remove trailing slash unless it's root
        if (strlen($normalized) > 1) {
            $normalized = rtrim($normalized, '/');
        }
        return $normalized;
    }
    /**
     * Get the base path of the application.
     */
    protected function setGlobal():void{
        $GLOBALS['app'] = $this;
    }
    protected function showErrors(bool $debug): void
    {
        if ($debug) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        }
    }
    /**
     * Register core services in the container.
     */
    protected function registerCoreServices(): void
    {
        // Register Database Connection
        $this->container->singleton('db', function () {
            $config = $this->container->make('config')['db'] ?? [];
            if (empty($config)) {
                throw new FrameworkException('Database configuration not found.');
            }

            return new DatabaseConnection($config);
        });

        // Register the router
        $this->container->singleton('router', function ($container) {
            return new Router($container);
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
            foreach($this->paths as $key => $path){
                
                if ($key !== 'base') {
                    $path = $this->paths['base'].$path;
                }else{
                    $path = $this->paths['base'];
                }
                $fileLoader->mapDirectory($key, $path);
            }
            return $fileLoader;
        });

        // Register the directory mapper
        $this->container->singleton('directoryMapper', function () {
            $paths = [];
            foreach ($this->paths as $key => $path) {
                $paths[$key] = ($key === 'base') ? $this->paths['base'] : $this->paths['base'].$path;
            }
            return new DirectoryMapper($paths);
        });


    }

    /**
     * Register global middleware.
     */
    protected function registerGlobalMiddleware(): void
    {
        $router = $this->container->make('router');
        $router->middleware([
            SessionMiddleware::class,
        ]);
    }

    public function registerTemplateServices(): void
    {
        // Register the template engine
        $this->container->singleton('template', function ($container) {
            //GET A DATA MODEL FOR THE TEMPLATE ENGINE
            $templateModel = $container->make('templateModel');
            //GET A FILE LOADER FOR THE TEMPLATE ENGINE
            $fileLoader = $container->make('fileLoader');
            return new TemplateEngine(
                $fileLoader,
                $templateModel
            );
        });

        $this->container->singleton('templateModel', function () {
            $db = $this->container->make('db');
            //INITIALIZE THE TEMPLATE MODEL WITH THESE VALUES
            return new TemplateModel($db);
        });
    }

    /**
     * Register service providers.
     */
    protected function registerProviders(): void
    {
        // Example: Register additional service providers here
        // $this->container->make(SomeServiceProvider::class)->register();
    }

    /**
     * Load routes from the routes directory.
     */
    protected function loadRoutes(): void
    {
        require_once $this->routesPath('web.php');
    }

    /**
     * Boot the application.
     */
    public function boot(): void
    {
        $this->loadRoutes();
    }

    /**
     * Run the application.
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
     * Handle an exception.
     */
    protected function handleException(\Exception $e): void
    {
        $statusCode = $e instanceof FrameworkException ? $e->getCode() : 500;

        if (!$statusCode || $statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        if ($this->debug) {
            $response = new Response(
                '<h1>Error: '.$e->getMessage().'</h1>'.
                '<p>File: '.$e->getFile().' (Line: '.$e->getLine().')</p>'.
                '<pre>'.$e->getTraceAsString().'</pre>',
                $statusCode,
                ['Content-Type' => 'text/html']
            );
        } else {
            $response = new Response(
                '<h1>Server Error</h1>'.
                '<p>Sorry, something went wrong on our servers.</p>',
                $statusCode,
                ['Content-Type' => 'text/html']
            );
        }

        $response->send();
    }

    /**
     * Set the application environment.
     */
    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    /**
     * Set debug mode.
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
    public function getDebug(): bool
    {
        return $this->debug;
    }
    /**
     * Get the application container.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get the application path.
     */
    public function appPath(): string
    {
        return $this->paths['base'].$this->paths['appPath'];
    }

    /**
     * Get the configuration path.
     */
    public function configPath(): string
    {
        return $this->paths['base'].$this->paths['config'];
    }

    /**
     * Get the routes path.
     */
    public function routesPath(string $file = ''): string
    {
        return $this->paths['base'].$this->paths['routes'].($file ? '/'.$file : '');
    }

    /**
     * Get the templates path.
     */
    public function templatesPath(): string
    {
        return $this->paths['base'].$this->paths['templates'];
    }

    public function assetsPath(): string
    {
        return $this->paths['base'].$this->paths['assets'];
    }
    public function basePath(): string
    {
        return $this->paths['base'];
    }
    public function getPaths(): array
    {
        return $this->paths;
    }   
}