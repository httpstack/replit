<?php

namespace Framework\Data;

use Framework\Exceptions\FrameworkException;

/**
 * JSON Data Source
 * 
 * Handles loading and manipulating JSON data from files or APIs
 */
class JsonDataSource
{
    /**
     * The data from the JSON source
     * 
     * @var array
     */
    protected array $data = [];
    
    /**
     * The source file path or URL
     * 
     * @var string|null
     */
    protected ?string $source = null;
    
    /**
     * Create a new JSON data source
     * 
     * @param string|array $source
     * @throws FrameworkException
     */
    public function __construct($source = null)
    {
        if (is_string($source)) {
            $this->source = $source;
            $this->loadFromSource();
        } elseif (is_array($source)) {
            $this->data = $source;
        }
    }
    
    /**
     * Load data from the source
     * 
     * @return $this
     * @throws FrameworkException
     */
    public function loadFromSource(): self
    {
        if ($this->source === null) {
            throw new FrameworkException("No source specified for JSON data");
        }
        
        // Check if it's a URL
        if (filter_var($this->source, FILTER_VALIDATE_URL)) {
            $this->loadFromUrl();
        } else {
            $this->loadFromFile();
        }
        
        return $this;
    }
    
    /**
     * Load data from a file
     * 
     * @return $this
     * @throws FrameworkException
     */
    protected function loadFromFile(): self
    {
        if (!file_exists($this->source)) {
            throw new FrameworkException("JSON file not found: {$this->source}");
        }
        
        $content = file_get_contents($this->source);
        
        if ($content === false) {
            throw new FrameworkException("Failed to read JSON file: {$this->source}");
        }
        
        $this->parseJson($content);
        
        return $this;
    }
    
    /**
     * Load data from a URL
     * 
     * @return $this
     * @throws FrameworkException
     */
    protected function loadFromUrl(): self
    {
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true,
                'header' => [
                    'User-Agent: PHP/Framework'
                ]
            ]
        ]);
        
        $content = @file_get_contents($this->source, false, $context);
        
        if ($content === false) {
            throw new FrameworkException("Failed to fetch JSON from URL: {$this->source}");
        }
        
        $this->parseJson($content);
        
        return $this;
    }
    
    /**
     * Parse JSON content
     * 
     * @param string $content
     * @return void
     * @throws FrameworkException
     */
    protected function parseJson(string $content): void
    {
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FrameworkException("Invalid JSON: " . json_last_error_msg());
        }
        
        $this->data = $data;
    }
    
    /**
     * Save the current data to a file
     * 
     * @param string|null $filePath
     * @param int $options
     * @return bool
     * @throws FrameworkException
     */
    public function save(?string $filePath = null, int $options = 0): bool
    {
        $path = $filePath ?? $this->source;
        
        if (!is_string($path)) {
            throw new FrameworkException("No file path specified for saving JSON data");
        }
        
        $json = json_encode($this->data, $options | JSON_PRETTY_PRINT);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FrameworkException("Failed to encode data to JSON: " . json_last_error_msg());
        }
        
        $result = file_put_contents($path, $json);
        
        if ($result === false) {
            throw new FrameworkException("Failed to write JSON file: {$path}");
        }
        
        return true;
    }
    
    /**
     * Get the entire data set
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * Set the entire data set
     * 
     * @param array $data
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
    
    /**
     * Get a value by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Set a value by key
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Check if a key exists
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }
    
    /**
     * Remove a key
     * 
     * @param string $key
     * @return $this
     */
    public function remove(string $key): self
    {
        unset($this->data[$key]);
        return $this;
    }
    
    /**
     * Filter the data using a callback
     * 
     * @param callable $callback
     * @return array
     */
    public function filter(callable $callback): array
    {
        return array_filter($this->data, $callback);
    }
    
    /**
     * Map the data using a callback
     * 
     * @param callable $callback
     * @return array
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->data);
    }
    
    /**
     * Find an item by key and value
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function find(string $key, $value)
    {
        foreach ($this->data as $item) {
            if (is_array($item) && isset($item[$key]) && $item[$key] === $value) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * Find all items by key and value
     * 
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function findAll(string $key, $value): array
    {
        $results = [];
        
        foreach ($this->data as $item) {
            if (is_array($item) && isset($item[$key]) && $item[$key] === $value) {
                $results[] = $item;
            }
        }
        
        return $results;
    }
}