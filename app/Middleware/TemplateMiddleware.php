<?php

namespace App\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class TemplateMiddleware
{
    protected $app;
    protected $container;
    protected $template;
    public function __construct()
    {
        // Constructor logic if needed
        global $app;
        $this->app = $app;
        $this->container = $app->getContainer();
        $this->template = $this->container->make('template');
    }
    public function index(Request $request, callable $next): Response
    {

        // Call the process method to handle the request
        return $this->process($request, $next);
    }

    public function process(Request $request, callable $next): Response
    {
        $response = new Response();
        $template = $this->app->getContainer()->make('template');
        $doc = $this->template->loadTemplate("layouts/base");
        $this->template->loadHTML($doc);
       
        // Call the next middleware or handler
        $response = $next($request);        

        $response->setHeader('mw-template', 'Loaded');
   
        
        return $response;
    }

}