<?php

namespace App\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class TemplateMiddleware
{
    public function index(Request $request, callable $next): Response
    {
        // Call the process method to handle the request
        return $this->process($request, $next);
    }

    public function process(Request $request, callable $next): Response
    {
        
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