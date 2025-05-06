<?php

namespace Framework\Controller;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Template\TemplateEngine;
use Framework\Container\Container;

/**
 * Base Controller
 * 
 * Provides common functionality for controllers
 */
abstract class BaseController
{
    /**
     * The container instance
     * 
     * @var Container
     */
    protected Container $container;
    
    /**
     * The template engine
     * 
     * @var TemplateEngine|null
     */
    protected ?TemplateEngine $template = null;
    
    /**
     * Create a new controller instance
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        
        // Try to resolve the template engine
        if ($container->has('template')) {
            $this->template = $container->make('template');
        }
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $name
     * @return mixed
     */
    protected function get(string $name)
    {
        return $this->container->make($name);
    }
    
    /**
     * Create a view response
     * 
     * @param string $view
     * @param array $data
     * @param int $status
     * @return Response
     */
    protected function view(string $view, array $data = [], int $status = 200): Response
    {
        if (!$this->template) {
            throw new \RuntimeException('Template engine not available');
        }
        
        $content = $this->template->render($view, $data);
        
        return new Response($content, $status, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }
    
    /**
     * Create a JSON response
     * 
     * @param mixed $data
     * @param int $status
     * @return Response
     */
    protected function json($data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
    
    /**
     * Create a redirect response
     * 
     * @param string $url
     * @param int $status
     * @return Response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
    
    /**
     * Get the URL generator
     * 
     * @return mixed
     */
    protected function url()
    {
        return $this->get('router');
    }
    
    /**
     * Generate a URL for a named route
     * 
     * @param string $name
     * @param array $parameters
     * @return string
     */
    protected function route(string $name, array $parameters = []): string
    {
        return $this->url()->url($name, $parameters);
    }
    
    /**
     * Get data from the request
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function request(?string $key = null, $default = null)
    {
        $request = $this->get('request');
        
        if ($key === null) {
            return $request;
        }
        
        return $request->get($key, $default);
    }
    
    /**
     * Get data from the session
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function session(?string $key = null, $default = null)
    {
        $session = $this->get('session');
        
        if ($key === null) {
            return $session;
        }
        
        return $session->get($key, $default);
    }
    
    /**
     * Flash data to the session
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function flash(string $key, $value): void
    {
        $this->get('session')->flash($key, $value);
    }
    
    /**
     * Validate request data
     * 
     * @param array $rules
     * @return array
     */
    protected function validate(array $rules): array
    {
        $data = $this->request()->all();
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleParts = explode('|', $rule);
            
            foreach ($ruleParts as $rulePart) {
                $params = [];
                
                if (strpos($rulePart, ':') !== false) {
                    list($ruleName, $ruleParams) = explode(':', $rulePart, 2);
                    $params = explode(',', $ruleParams);
                } else {
                    $ruleName = $rulePart;
                }
                
                // Call the validation method
                $methodName = 'validate' . ucfirst($ruleName);
                
                if (method_exists($this, $methodName)) {
                    $result = $this->$methodName($field, $data[$field] ?? null, $params);
                    
                    if ($result !== true) {
                        $errors[$field][] = $result;
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            // Store errors in flash data
            $this->flash('errors', $errors);
            $this->flash('old', $data);
            
            // If an error handler is defined, call it
            if (method_exists($this, 'handleValidationErrors')) {
                return $this->handleValidationErrors($errors);
            }
        }
        
        return array_intersect_key($data, $rules);
    }
    
    /**
     * Validate that a field is required
     * 
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateRequired(string $field, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return "The {$field} field is required.";
        }
        
        return true;
    }
    
    /**
     * Validate that a field is a valid email
     * 
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateEmail(string $field, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$field} field must be a valid email address.";
        }
        
        return true;
    }
    
    /**
     * Validate that a field has a minimum length
     * 
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateMin(string $field, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $min = (int) ($params[0] ?? 0);
        
        if (is_string($value) && mb_strlen($value) < $min) {
            return "The {$field} field must be at least {$min} characters.";
        }
        
        if (is_numeric($value) && $value < $min) {
            return "The {$field} field must be at least {$min}.";
        }
        
        return true;
    }
    
    /**
     * Validate that a field has a maximum length
     * 
     * @param string $field
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateMax(string $field, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $max = (int) ($params[0] ?? 0);
        
        if (is_string($value) && mb_strlen($value) > $max) {
            return "The {$field} field must not exceed {$max} characters.";
        }
        
        if (is_numeric($value) && $value > $max) {
            return "The {$field} field must not exceed {$max}.";
        }
        
        return true;
    }
}
