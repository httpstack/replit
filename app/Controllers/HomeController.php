<?php

namespace App\Controllers;

use Framework\Http\Request;
use Framework\Http\Response;

/**
 * Home Controller.
 *
 * Controller for the home page and main functionality
 */
class HomeController
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

    /**
     * Display the home page.
     */
    public function index(Request $request): Response
    {
        $response = new Response();
        // var_dump($this->template->fileLoader->findFile('style.css', null, 'css')); // ;
        $this->template->injectView('home/index', 'viewContent');
        $this->template->processData('template');
        $response->setBody($this->template->saveHTML());
        // $response->setBody('<h1>Home Page</h1><p>Welcome to the home page.</p>');

        return $response;
    }

    /**
     * Display the about page.
     */
    public function about(Request $request): Response
    {
        $response = new Response();
        $response->setBody('<h1>About Us</h1><p>This is the about page.</p>');

        return $response;
    }
}