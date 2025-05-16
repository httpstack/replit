<?php

/**
 * Application Configuration
 * 
 * This file contains the main application configuration settings
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'name' => 'Custom MVC Framework',
    'slogan' => 'A simple MVC framework for PHP',
    'version' => '1.0.0',
    'author' => 'Chris McIntosh',
    'author_email' => 'chris@httpstack.tech',
    'copyright' => '2023 Chris McIntosh',
    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */
    'env' => 'development',
    
    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */
    'debug' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the framework to generate URLs properly.
    | You should set this to the root of your application.
    |
    */
    'url' => 'http://localhost/replit/',
    
    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions.
    |
    */
    'timezone' => 'UTC',
    
    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => 'en',
    
    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Add your own service providers to this array.
    |
    */
    'providers' => [
        // Framework Service Providers
        
        // Application Service Providers
        App\Providers\AppServiceProvider::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Global middleware that should run on every request.
    |
    */
    'middleware' => [
        Framework\Middleware\SessionMiddleware::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Template Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the template engine
    |
    */
    'template' => [
        'cache' => false,
        'directory' => '/templates',
    ],
];
