<?php

namespace App\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Middleware\MiddlewareInterface;

/**
 * Example Middleware
 * 
 * An example middleware to demonstrate the middleware system
 */
class ExampleMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function index(Request $request, callable $next): Response
    {
        echo "Middleware index method called\n";
        // Call the process method to handle the request
        return $this->process($request, $next);
    }
    public function process(Request $request, callable $next): Response
    {
        // Perform actions before the request is handled
        
        // For example, add a custom header to the request
       //$request->setHeader('X-Example-Middleware', 'processed');
        
        // Or check a condition before allowing the request to proceed
        if ($request->getMethod() === 'POST' && !$request->has('_token')) {
            // Throw an exception or redirect
            return new Response('CSRF token missing', 403);
        }
        
        // Call the next middleware or handler
        $response = $next($request);        
        // Perform actions after the response is generated
        
        // For example, add a custom header to the response
        $response->setHeader('X-Powered-By', 'Custom MVC Framework 123');
        
        return $response;
    }
}
