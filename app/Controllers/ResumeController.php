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
class ResumeController
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
     * Display the Resume page.
     */
    public function index(Request $request): Response
    {
        $view = "resume/index";
        $this->template->assign("mykey", "myvalue");
        $this->template->assign("title", "Resume");
        $this->template->injectView($view, 'viewContent');
        //$this->template->processData('template');
        //echo $this->template->getVars()['links'];
        $this->template->processHandlebars();
        $this->response->setBody($this->template->saveHTML());
        //Somethin must be set to the Response body
        if(!$this->response->getContent()){
            $this->response->setBody("Resume Page. No Template");
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