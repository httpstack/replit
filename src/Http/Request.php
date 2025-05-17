<?php

namespace Framework\Http;

/**
 * HTTP Request Class.
 *
 * Represents an HTTP request with convenient access to request data
 */
class Request
{
    /**
     * All request parameters.
     */
    protected array $parameters = [];

    /**
     * Request body content.
     */
    protected ?string $content = null;

    /**
     * Server parameters.
     */
    protected array $server = [];

    /**
     * Request headers.
     */
    protected array $headers = [];

    /**
     * Request cookies.
     */
    protected array $cookies = [];

    /**
     * Request files.
     */
    protected array $files = [];

    /**
     * Request path.
     */
    protected string $path;

    /**
     * Request method.
     */
    protected string $method;

    /**
     * Create a new request instance from globals.
     */
    public static function capture(): static
    {
        $request = new static();
        $request->initialize(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $_SERVER
        );

        return $request;
    }

    public function getBody()
    {
        return $this->content;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getUri()
    {
        return $this->server['REQUEST_URI'];
    }

    /**
     * Initialize the request.
     */
    public function initialize(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = []
    ): void {
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->parameters = array_merge($query, $request, $attributes);

        // Parse headers from server variables
        $this->headers = $this->parseHeaders();

        // Parse the request URI
        $this->path = $this->parsePath();

        // Get the request method
        $this->method = $this->server['REQUEST_METHOD'] ?? 'GET';

        // Handle json content
        $this->parseJsonContent();
    }

    /**
     * Parse the request headers from server variables.
     */
    protected function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Parse the request path from REQUEST_URI.
     */
    protected function parsePath(): string
    {
        $requestUri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH);

        return $path ?: '/';
    }

    /**
     * Parse JSON content if the content type is application/json.
     */
    protected function parseJsonContent(): void
    {
        $contentType = $this->headers['content-type'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $content = file_get_contents('php://input');
            if ($content) {
                $this->content = $content;
                $jsonData = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $this->parameters = array_merge($this->parameters, $jsonData);
                }
            }
        }
    }

    /**
     * Get the request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Check if the request method matches the given method.
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    /**
     * Check if the request is an AJAX request.
     */
    public function isAjax(): bool
    {
        return $this->headers['x-requested-with'] === 'XMLHttpRequest';
    }

    /**
     * Get the request path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get a request parameter.
     */
    public function get(string $key, $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Get a query parameter (GET).
     */
    public function getQuery(string $key, $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get a post parameter (POST).
     */
    public function getPost(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }

        return $_POST[$key] ?? $default;
    }

    /**
     * Check if a parameter exists.
     */
    public function has(string $key): bool
    {
        return isset($this->parameters[$key]);
    }

    /**
     * Get all request parameters.
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Get only specified parameters.
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->parameters, array_flip($keys));
    }

    /**
     * Get all parameters except the specified ones.
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->parameters, array_flip($keys));
    }

    /**
     * Get the raw request content.
     */
    public function getContent(): ?string
    {
        if ($this->content === null) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }

    /**
     * Get a request header.
     */
    public function header(string $key, $default = null): mixed
    {
        $key = strtolower($key);

        return $this->headers[$key] ?? $default;
    }

    /**
     * Get all request headers.
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get a cookie.
     */
    public function cookie(string $key, $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get a server parameter.
     */
    public function server(string $key, $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get a file from the request.
     */
    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get the client IP address.
     */
    public function getIp(): string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (isset($this->server[$header])) {
                $ips = explode(',', $this->server[$header]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get the URI query string.
     */
    public function getQueryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    /**
     * Get the request scheme (http/https).
     */
    public function getScheme(): string
    {
        return isset($this->server['HTTPS'])
               && $this->server['HTTPS'] !== 'off' ? 'https' : 'http';
    }

    /**
     * Get the request host.
     */
    public function getHost(): string
    {
        if (isset($this->server['HTTP_HOST'])) {
            return $this->server['HTTP_HOST'];
        }

        return $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get the full URL.
     */
    public function getUrl(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $uri = $this->server['REQUEST_URI'] ?? '/';

        return "$scheme://$host$uri";
    }
}