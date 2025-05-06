<?php

namespace App\Controllers;

use Framework\Controller\BaseController;
use Framework\Http\Request;
use Framework\Http\Response;

/**
 * Home Controller
 * 
 * Controller for the home page and main functionality
 */
class HomeController extends BaseController
{
    /**
     * Display the home page
     * 
     * @return Response
     */
    public function index(): Response
    {
        return $this->view('home/index', [
            'title' => 'Custom MVC Framework',
            'message' => 'Welcome to your custom PHP MVC framework',
            'features' => [
                'MVC Architecture',
                'Service Container',
                'Advanced Routing',
                'Middleware Support',
                'DOM-based Templating',
                'Advanced File Loading',
                'Data Binding',
            ]
        ]);
    }
    
    /**
     * Display the about page
     * 
     * @return Response
     */
    public function about(): Response
    {
        return $this->view('home/about', [
            'title' => 'About the Framework',
            'description' => 'This is a custom PHP MVC framework with advanced features',
        ]);
    }
    
    /**
     * Example of JSON response
     * 
     * @return Response
     */
    public function api(): Response
    {
        return $this->json([
            'framework' => 'Custom MVC',
            'version' => '1.0.0',
            'features' => [
                'MVC Architecture',
                'Service Container',
                'Advanced Routing',
                'Middleware Support',
                'DOM-based Templating',
                'Advanced File Loading',
                'Data Binding',
            ],
            'timestamp' => time(),
        ]);
    }
    
    /**
     * Form handling example
     * 
     * @param Request $request
     * @return Response
     */
    public function contact(Request $request): Response
    {
        // For GET requests, display the form
        if ($request->isMethod('GET')) {
            return $this->view('home/contact', [
                'title' => 'Contact Us',
            ]);
        }
        
        // For POST requests, validate the form
        $data = $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required|min:10',
        ]);
        
        // If validation fails, it will automatically redirect back
        
        // Process the form data
        // ... (e.g., send email, save to database, etc.)
        
        // Flash a success message
        $this->flash('success', 'Your message has been sent!');
        
        // Redirect back to the contact page
        return $this->redirect('/contact');
    }
}
