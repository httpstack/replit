<?php

namespace App\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class TemplateMiddleware
{
    protected $app;
    protected $container;
    protected $template;
    protected $config;

    public function __construct()
    {
        // Constructor logic if needed
        global $app;
        $this->app = $app;
        $this->container = $app->getContainer();
        $this->template = $this->container->make('template');
        $this->config = $this->container->make('config')['template'];
        $this->template->assign($this->config);
    }

    public function index(Request $request, callable $next): Response
    {
        // Call the process method to handle the request
        return $this->process($request, $next);
    }

    public function process(Request $request, callable $next): Response
    {
        $response = new Response();
        // Prepare the assets and the exp replacement vars
        $doc = $this->template->loadTemplate('layouts/base');
        $this->template->loadHTML($doc);
        $assets = $this->container->make('getAssets');
        var_dump($assets);
        $this->template->processData('template');

        // Call the next middleware or handler
        $response = $next($request);

        $response->setHeader('mw-template', 'Loaded');

        return $response;
    }
}
