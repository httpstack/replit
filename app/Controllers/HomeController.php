<?php

namespace App\Controllers;

use Framework\Http\Request;
use Framework\Http\Response;

/**
 * Home Controller
 * 
 * Controller for the home page and main functionality
 */
class HomeController
{
    /**
     * Display the home page
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {

        $response->setHeader('Content-Type', 'text/html');
        $final = "<h1>Welcome to the Home Page</h1>";
        $response->setContent($final);
        
        return $response;
    }           

    /**
     * Display the about page
     * 
     * @param Request $request
     * @return Response
     */
    public function about(Request $request): Response
    {
        $response = new Response();
        $response->setBody('<h1>About Us</h1><p>This is the about page.</p>');
        return $response;
    }
}
