<?php

namespace Framework\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\Session;

/**
 * Session Middleware
 * 
 * Initializes the session for each request
 */
class SessionMiddleware implements MiddlewareInterface
{
    /**
     * Session instance
     * 
     * @var Session
     */
    protected Session $session;
    
    /**
     * Create a new session middleware
     * 
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }
    
    /**
     * Process an incoming request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function process(Request $request, callable $next): Response
    {
        // Start the session
        $this->session->start();
        
        // Call the next middleware
        $response = $next($request);
        
        return $response;
    }
}
