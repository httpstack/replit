<?php

use Framework\Core\Application;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\Session;
use Framework\Routing\Router;
use Framework\Template\TemplateEngine;
use Framework\Template\DomManipulator;
use Framework\FileSystem\FileLoader;
use Framework\FileSystem\DirectoryMapper;
use Framework\Middleware\SessionMiddleware;

/**
 * Bootstrap the application
 * 
 * This file creates and configures the application instance
 */

// Create the application
$app = new Application(BASE_PATH);

// Load configuration
$config = $app->config('app');

// Set application environment and debug mode
$app->setEnvironment($config['env'] ?? 'production');
$app->setDebug($config['debug'] ?? false);

// Register core services
$app->singleton('router', function ($container) {
    return new Router($container);
});

$app->singleton('request', function () {
    return Request::capture();
});

$app->singleton('session', function () {
    return new Session();
});

$app->singleton('fileLoader', function () use ($app) {
    $fileLoader = new FileLoader();
    $fileLoader->mapDirectory('app', $app->appPath());
    $fileLoader->mapDirectory('config', $app->configPath());
    $fileLoader->mapDirectory('routes', $app->routesPath());
    $fileLoader->mapDirectory('templates', $app->templatesPath());
    
    return $fileLoader;
});

$app->singleton('directoryMapper', function () use ($app) {
    return new DirectoryMapper([
        'app' => $app->appPath(),
        'config' => $app->configPath(),
        'routes' => $app->routesPath(),
        'templates' => $app->templatesPath(),
    ]);
});

$app->singleton('template', function ($container) use ($app) {
    $fileLoader = $container->make('fileLoader');
    $dom = new DomManipulator();
    
    return new TemplateEngine(
        $fileLoader,
        $dom,
        $app->templatesPath()
    );
});

// Register global middleware
$router = $app->get('router');
$router->middleware([
    SessionMiddleware::class,
]);

// Register service providers
$app->registerProviders();

// Load routes
require_once $app->routesPath('web.php');

// Boot the application
$app->boot();

return $app;
