<?php

namespace App\Providers;

use Framework\Container\ServiceProvider;
use App\Models\ExampleModel;

/**
 * App Service Provider
 * 
 * Register application services and perform application bootstrapping
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services with the container
     * 
     * @return void
     */
    public function register(): void
    {
        // Register singleton services
        $this->container->singleton('example', function () {
            return new ExampleModel([
                'name' => 'Example Model',
                'email' => 'example@example.com',
                'description' => 'This is an example model singleton',
                'active' => true,
            ]);
        });
        
        // Register other services
        $this->container->bind('exampleFactory', function () {
            return function (array $attributes = []) {
                return new ExampleModel($attributes);
            };
        });
    }
    
    /**
     * Bootstrap any application services
     * 
     * @return void
     */
    public function boot(): void
    {
        // Perform bootstrapping tasks
        
        // For example, set default template variables
        if ($this->container->has('template')) {
            $template = $this->container->make('template');
            
            $template->assign([
                'appName' => config('app.name', 'Custom MVC Framework'),
                'appVersion' => config('app.version', '1.0.0'),
                'year' => date('Y'),
            ]);
        }
    }
}
