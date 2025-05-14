<?php

namespace Framework\Container;

/**
 * Abstract Service Provider
 * 
 * Base class for all service providers to register services with the container
 */
abstract class ServiceProvider
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;
    
    /**
     * Create a new service provider instance.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Register services with the container.
     *
     * @return void
     */
    abstract public function register(): void;
    
    /**
     * Boot any application services after registration.
     *
     * @return void
     */
    public function boot(): void
    {
        // Base implementation does nothing
    }
}
