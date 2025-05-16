<?php

namespace App\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class TemplateMiddleware
{
    protected $app;
    protected $container;
    protected $template;
    public function index(Request $request, callable $next): Response
    {
        global $app;
        $this->app = $app;
        $this->container = $app->getContainer();
        $this->template = $this->container->make('template');
        // Call the process method to handle the request
        return $this->process($request, $next);
    }

    public function process(Request $request, callable $next): Response
    {
        $response = new Response();
        $template = $GLOBALS['app']->getContainer()->make('template');
        $txt = $template->render("main.html", [
            'title' => 'My Custom MVC Framework'
        ]);
        // Call the next middleware or handler
        $response = $next($request);        

        $response->setHeader('X-Powered-By', 'Custom MVC Framework 123');
   
        $response->setContent($txt);
        return $response;
    }

}