<?php

namespace Framework\Http;

/**
 * Session Management Class
 * 
 * Handles session operations with a clean interface
 */
class Session
{
    /**
     * Whether the session has been started
     * 
     * @var bool
     */
    protected bool $started = false;
    
    /**
     * Initialize the session
     * 
     * @return bool
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return true;
        }
        
        // Set secure session settings
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        
        // Start the session
        $this->started = session_start();
        
        return $this->started;
    }
    
    /**
     * Store data in the session
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Retrieve data from the session
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if a key exists in the session
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove data from the session
     * 
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }
    
    /**
     * Get all session data
     * 
     * @return array
     */
    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION;
    }
    
    /**
     * Remove all data from the session
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
    }
    
    /**
     * Destroy the session completely
     * 
     * @return bool
     */
    public function destroy(): bool
    {
        if ($this->started) {
            $this->started = false;
            
            // Clear the session data
            $_SESSION = [];
            
            // Delete the session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            // Destroy the session
            return session_destroy();
        }
        
        return true;
    }
    
    /**
     * Get the session ID
     * 
     * @return string
     */
    public function getId(): string
    {
        $this->ensureStarted();
        return session_id();
    }
    
    /**
     * Regenerate the session ID
     * 
     * @param bool $deleteOldSession
     * @return bool
     */
    public function regenerateId(bool $deleteOldSession = false): bool
    {
        $this->ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Store flash data in the session
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function flash(string $key, $value): void
    {
        $this->ensureStarted();
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Retrieve flash data from the session
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFlash(string $key, $default = null): mixed
    {
        $this->ensureStarted();
        
        $value = $_SESSION['_flash'][$key] ?? $default;
        
        // Remove the flash data after retrieval
        if (isset($_SESSION['_flash'][$key])) {
            unset($_SESSION['_flash'][$key]);
        }
        
        return $value;
    }
    
    /**
     * Check if flash data exists
     * 
     * @param string $key
     * @return bool
     */
    public function hasFlash(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION['_flash'][$key]);
    }
    
    /**
     * Get all flash data
     * 
     * @return array
     */
    public function getFlashes(): array
    {
        $this->ensureStarted();
        
        $flashes = $_SESSION['_flash'] ?? [];
        
        // Clear all flash data
        $_SESSION['_flash'] = [];
        
        return $flashes;
    }
    
    /**
     * Ensure the session is started
     * 
     * @return void
     */
    protected function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }
}
