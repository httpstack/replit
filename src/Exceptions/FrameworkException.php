<?php

namespace Framework\Exceptions;

/**
 * Framework Exception
 * 
 * Base exception class for all framework-specific exceptions
 */
class FrameworkException extends \Exception
{
    /**
     * Create a new framework exception
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Create a new not found exception
     * 
     * @param string $message
     * @return static
     */
    public static function notFound(string $message = "Not found"): self
    {
        return new static($message, 404);
    }
    
    /**
     * Create a new unauthorized exception
     * 
     * @param string $message
     * @return static
     */
    public static function unauthorized(string $message = "Unauthorized"): self
    {
        return new static($message, 401);
    }
    
    /**
     * Create a new forbidden exception
     * 
     * @param string $message
     * @return static
     */
    public static function forbidden(string $message = "Forbidden"): self
    {
        return new static($message, 403);
    }
    
    /**
     * Create a new bad request exception
     * 
     * @param string $message
     * @return static
     */
    public static function badRequest(string $message = "Bad request"): self
    {
        return new static($message, 400);
    }
    
    /**
     * Create a new server error exception
     * 
     * @param string $message
     * @return static
     */
    public static function serverError(string $message = "Server error"): self
    {
        return new static($message, 500);
    }
    
    /**
     * Create a new method not allowed exception
     * 
     * @param string $message
     * @return static
     */
    public static function methodNotAllowed(string $message = "Method not allowed"): self
    {
        return new static($message, 405);
    }
}
