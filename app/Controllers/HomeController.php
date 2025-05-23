<?php

namespace App\Controllers;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Traits\TemplateUtility;

/**
 * Home Controller.
 *
 * Controller for the home page and main functionality
 */
class HomeController
{
    use TemplateUtility;
    protected $app;
    protected $container;
    protected $template;
    protected $response;

    public function __construct()
    {
        // Constructor logic if needed
        global $app;
        $this->app = $app;
        $this->container = $app->getContainer();
        $this->template = $this->container->make('template');
        $this->response = new Response();
    }

    /**
     * Display the home page.
     */
    public function index(Request $request): Response
    {
        $view = "home/index";
        $this->template->assign("mykey", "myvalue");
        $this->template->assign("title", "Home");
        $this->template->injectView($view, 'viewContent');
        //$this->template->processData('template');
        $this->template->processHandlebars();
        $this->response->setBody($this->template->saveHTML());
        //$this->applyTemplate('home/index');
        //Somethin must be set to the Response body
        if(!$this->response->getContent()){
            $this->response->setBody("Home Page. No Template");
        }
        return $this->response;
    }

    /**
     * Display the about page.
     */
    public function about(Request $request): Response
    {
        $this->response->setBody('<h1>About Us</h1><p>This is the about page.</p>');

        return $this->response;
    }
}