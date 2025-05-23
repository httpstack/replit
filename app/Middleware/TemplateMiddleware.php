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
        $this->app = app();
        // $this->app->registerTemplateServices();

        $this->container = $this->app->getContainer();
        $this->template = $this->container->make('template',['container' => $this->container]);
        $this->templateModel = $this->template->templateModel;

        //GET THE CONFIGURATION FOR THE TEMPLATE AND LOADINTO MODEL
        $this->config = $this->container->make('config')['template'];
        $this->templateModel->fill($this->config);

        //GET THE JSON REPLACEMENT DATA AND LOAD INTO MODEL
        $baseData = $this->config['baseData'] ?? 'base';
        $baseData = $this->container->make('fileLoader')->findFile($baseData, null, 'json');
        $this->templateModel->loadJsonData($baseData);
        
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
        //USING THE DOM UTIL TRAITS, BUILD A NAVBAR WITH THEW LINKS ARRAY
        $links = $this->templateModel->getAttribute('links');
        $html = "<ul>";
        foreach($links as $index => $link){
            if($link['type'] === 'main')
            {
                $uri = $link["uri"];
                $label = $link['label'];
                $html .= "<li><a href='$uri'>$label</a></li>";           
            }
        }
        $html .= "</ul>";
        $links = $html;
        //ASSIGN GENERATED HTML TO THE REPLACEMENT DATA
        $this->template->assign($this->templateModel->getAttributes());

        $doc = $this->template->loadTemplate($baseTemplate, true);
       // debug(array_keys($this->template->getVars()));
        //APPEND THE THE GENERATED LINK / SCRIPT TAGS TO THE HEAD OR BODY

        $this->template->loadAssets($assets);
        $this->template->assign("links", $links);
        $this->template->processData("template");
    
        // Call the next middleware or handler
        $response = $next($request);

        $response->setHeader('mw-template', 'Loaded');

        return $response;
    }
}