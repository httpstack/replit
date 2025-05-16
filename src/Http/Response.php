<?php

namespace Framework\Http;

/**
 * HTTP Response Class
 * 
 * Represents an HTTP response with content, headers, and status code
 */
class Response
{
    /**
     * Response content
     * 
     * @var string
     */
    protected string $content = '';
    
    /**
     * HTTP status code
     * 
     * @var int
     */
    protected int $statusCode = 200;
    
    /**
     * Response headers
     * 
     * @var array
     */
    protected array $headers = [];
    
    /**
     * HTTP status codes
     * 
     * @var array
     */
    protected static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
    ];
    
    /**
     * Create a new response instance
     * 
     * @param string $content
     * @param int $status
     * @param array $headers
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }
    
    /**
     * Set the response content
     * 
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }
    public function setBody(string $content): self
    {
        return $this->setContent($content);
    }
    /**
     * Get the response content
     * 
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
    
    /**
     * Set the response status code
     * 
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    /**
     * Get the response status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * Set a response header
     * 
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @return $this
     */
    public function setHeader(string $name, string $value, bool $replace = true): self
    {
        if ($replace || !isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        }
        return $this;
    }
    
    /**
     * Add multiple headers at once
     * 
     * @param array $headers
     * @param bool $replace
     * @return $this
     */
    public function addHeaders(array $headers, bool $replace = true): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value, $replace);
        }
        return $this;
    }
    
    /**
     * Get all headers
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    /**
     * Send the response to the client
     * 
     * @return void
     */
    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();
    }
    
    /**
     * Send the response headers to the client
     * 
     * @return $this
     */
    protected function sendHeaders(): self
    {
        if (headers_sent()) {
            return $this;
        }
        
        // Send status header
        $statusText = self::$statusTexts[$this->statusCode] ?? 'Unknown Status';
        header("HTTP/1.1 {$this->statusCode} {$statusText}", true, $this->statusCode);
        
        // Send other headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value", true);
        }
        
        return $this;
    }
    
    /**
     * Send the response content to the client
     * 
     * @return $this
     */
    protected function sendContent(): self
    {
        echo $this->content;
        return $this;
    }
    
    /**
     * Create a JSON response
     * 
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function json($data, int $status = 200, array $headers = []): static
    {
        $json = json_encode($data);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }
        
        $headers['Content-Type'] = 'application/json';
        
        return new static($json, $status, $headers);
    }
    
    /**
     * Create a redirect response
     * 
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function redirect(string $url, int $status = 302, array $headers = []): static
    {
        $headers['Location'] = $url;
        
        return new static('', $status, $headers);
    }
    
    /**
     * Create a HTML response
     * 
     * @param string $html
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function html(string $html, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        
        return new static($html, $status, $headers);
    }
    
    /**
     * Create a plain text response
     * 
     * @param string $text
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function text(string $text, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        
        return new static($text, $status, $headers);
    }

    /**
     * Create an empty response
     * 
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function empty(int $status = 204, array $headers = []): static
    {
        return new static('', $status, $headers);
    }
    
    /**
     * Set a cookie
     * 
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return $this
     */
    public function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): self {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        return $this;
    }
}
