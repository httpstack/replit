<?php

namespace Framework\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Container\Container;
use Framework\Exceptions\FrameworkException;

/**
 * Middleware Stack
 * 
 * Manages the execution of middleware in a chain pattern
 */
class MiddlewareStack
{
    /**
     * Container instance
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * Middleware stack
     * 
     * @var array
     */
    protected array $middleware = [];
    
    /**
     * Create a new middleware stack
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Add middleware to the stack
     * 
     * @param string|callable|MiddlewareInterface $middleware
     * @return $this
     */
    public function add($middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    /**
     * Handle the request through the middleware stack
     * 
     * @param Request $request
     * @param callable $core
     * @return Response
     * @throws FrameworkException
     */
    public function handle(Request $request, callable $core): Response
    {
        // Resolve the middleware stack
        $stack = function ($request) use ($core) {
            return $core($request);
        };
        
        // Build the middleware stack in reverse order
        $middleware = array_reverse($this->middleware);
        
        foreach ($middleware as $item) {
            $stack = $this->createMiddleware($item, $stack);
        }
        
        // Execute the middleware stack
        return $stack($request);
    }
    
    /**
     * Create a middleware callable
     * 
     * @param mixed $middleware
     * @param callable $next
     * @return callable
     * @throws FrameworkException
     */
    protected function createMiddleware($middleware, callable $next): callable
    {
        return function ($request) use ($middleware, $next) {
            if ($middleware instanceof MiddlewareInterface) {
                return $middleware->process($request, $next);
            }
            
            if (is_callable($middleware)) {
                return $middleware($request, $next);
            }
            
            if (is_string($middleware)) {
                $instance = $this->container->make($middleware);
                
                if ($instance instanceof MiddlewareInterface) {
                    return $instance->process($request, $next);
                }
                
                if (method_exists($instance, 'process')) {
                    return $instance->process($request, $next);
                }
            }
            
            throw new FrameworkException("Invalid middleware: " . (is_object($middleware) ? get_class($middleware) : $middleware));
        };
    }
}
