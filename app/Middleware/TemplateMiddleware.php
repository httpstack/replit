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
        // ho 'Executing TemplateMiddleware constructor'.PHP_EOL;
        global $app;
        $this->app = $app;
        // $this->app->registerTemplateServices();

        $this->container = $app->getContainer();
        $this->template = $this->container->make('template');
        $this->config = $this->container->make('config')['template'];

        // $this->template->assign($this->config);
        $this->templateModel = $this->container->make('templateModel');
        // cho $this->templateModel->getAttribute('baseTemplate');
    }

    public function index(Request $request, callable $next): Response
    {
        // Call the process method to handle the request
        return $this->process($request, $next);
    }

    public function process(Request $request, callable $next): Response
    {
        $response = new Response();
        /*
        *     LIST OF ALL VARS IN TEMPLATE 
        *     'baseTemplate' => 'layouts/base',
        *     'appLogo' => 'bx bx-logo',
        *     'appName' => 'My Application',
        *     'appSlogan' => 'Your application slogan here',
        *     'appVersion' => '1.0.0',
        *     'appDescription' => 'A simple PHP application',
        *     'appAuthor' => 'Your Name',
        *     'appCopyright' => '© 2023 Your Company All rights reserved.',
         */
        //Get the file path to the $baseTemplate, the array of $assets and the mlti-dim array of $links
        $baseTemplate = $this->templateModel->getAttribute('baseTemplate');
        $assets = $this->templateModel->getAttribute('assets');
        $links = $this->templateModel->getAttribute('links');

        //PUSH THE TEMPLATES Data Model to the template engine
        $this->template->assign($this->templateModel->getAttributes());
        // Prepare the assets and the exp replacement vars
        // echo method_exists($this->templateModel, 'getAttribute');
        $doc = $this->template->loadTemplate($baseTemplate, true);

        //APPEND THE THE GENERATED LINK / SCRIPT TAGS TO THE HEAD OR BODY
        //WITH DATASOURCE $assets
        $this->template->loadAssets($assets);

        // Call the next middleware or handler
        $response = $next($request);

        $response->setHeader('mw-template', 'Loaded');

        return $response;
    }
}