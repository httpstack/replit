<?php

namespace Framework\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

/**
 * Middleware Interface
 * 
 * Interface for all middleware classes
 */
interface MiddlewareInterface
{
    /**
     * Process an incoming request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function process(Request $request, callable $next): Response;
}
