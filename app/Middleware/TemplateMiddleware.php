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
    protected $templateModel;

    public function __construct()
    {
        // Constructor logic if needed
        global $app;
        $this->app = $app;
        $this->app->registerTemplateServices();
        $this->container = $app->getContainer();
        $this->template = $this->container->make('template');
        $this->config = $this->container->make('config')['template'];
        $this->template->assign($this->config);
        $this->templateModel = $this->container->make('templateModel');
    }

    public function index(Request $request, callable $next): Response
    {
        // Call the process method to handle the request
        return $this->process($request, $next);
    }

    public function process(Request $request, callable $next): Response
    {
        $response = new Response();
        $baseTemplate = $this->templateModel->getAttribute('baseTemplate');
        $assets = $this->templateModel->getAttribute('assets');
        // Prepare the assets and the exp replacement vars
        $doc = $this->template->loadTemplate($baseTemplate);
        $this->template->loadHTML($doc);

        // var_dump($assets);
        $this->template->loadAssets($assets);
        $this->template->processData('template');
        var_dump($this->templateModel);
        // Call the next middleware or handler
        $response = $next($request);

        $response->setHeader('mw-template', 'Loaded');

        return $response;
    }
}
