<?php

use Framework\Core\Application;
use Framework\Http\Response;

/**
 * Helper Functions
 * 
 * Common helper functions for use throughout the application
 */

if (!function_exists('app')) {
    /**
     * Get the available container instance
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|Application
     */
    function app(?string $abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return $GLOBALS['app'];
        }

        return $GLOBALS['app']->get($abstract);
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        list($file, $path) = explode('.', $key, 2) + [null, null];
        
        $config = app()->config($file);
        
        if ($path === null) {
            return $config;
        }
        
        $keys = explode('.', $path);
        $value = $config;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            
            $value = $value[$segment];
        }
        
        return $value;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @param int $status
     * @return Response
     */
    function view(string $view, array $data = [], int $status = 200): Response
    {
        $template = app('template');
        $content = $template->render($view, $data);
        
        return new Response($content, $status, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        return Response::redirect($url, $status, $headers);
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    function route(string $name, array $parameters = []): string
    {
        return app('router')->url($name, $parameters);
    }
}

if (!function_exists('session')) {
    /**
     * Get a session value
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function session(?string $key = null, $default = null)
    {
        $session = app('session');
        
        if ($key === null) {
            return $session;
        }
        
        return $session->get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value
     *
     * @return string
     */
    function csrf_token(): string
    {
        $session = app('session');
        
        if (!$session->has('_token')) {
            $token = bin2hex(random_bytes(32));
            $session->set('_token', $token);
        }
        
        return $session->get('_token');
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field
     *
     * @return string
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('method_field')) {
    /**
     * Generate a form field for spoofing the HTTP verb
     *
     * @param string $method
     * @return string
     */
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . $method . '">';
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL for an asset
     *
     * @param string $path
     * @return string
     */
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for the application
     *
     * @param string $path
     * @return string
     */
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        
        $request = app('request');
        $baseUrl = $request->getScheme() . '://' . $request->getHost();
        
        return $baseUrl . '/' . $path;
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old(string $key, $default = null)
    {
        $session = app('session');
        $old = $session->getFlash('old', []);
        
        return $old[$key] ?? $default;
    }
}

if (!function_exists('error')) {
    /**
     * Get validation error
     *
     * @param string $key
     * @return string|null
     */
    function error(string $key): ?string
    {
        $session = app('session');
        $errors = $session->getFlash('errors', []);
        
        return $errors[$key][0] ?? null;
    }
}

if (!function_exists('has_error')) {
    /**
     * Check if a validation error exists
     *
     * @param string $key
     * @return bool
     */
    function has_error(string $key): bool
    {
        $session = app('session');
        $errors = $session->getFlash('errors', []);
        
        return isset($errors[$key]);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters
     *
     * @param string $value
     * @return string
     */
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}
