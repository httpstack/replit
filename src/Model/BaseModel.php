<?php

namespace Framework\Model;

use Framework\Exceptions\FrameworkException;

/**
 * Base Model
 * 
 * Provides the base functionality for all models including data binding,
 * validation, and property access.
 */
class BaseModel
{
    /**
     * Model attributes
     * 
     * @var array
     */
    protected array $attributes = [];
    
    /**
     * Original attribute values
     * 
     * @var array
     */
    protected array $original = [];
    
    /**
     * The model's fillable attributes
     * 
     * @var array
     */
    protected array $fillable = [];
    
    /**
     * The model's validation rules
     * 
     * @var array
     */
    protected array $rules = [];
    
    /**
     * Validation errors
     * 
     * @var array
     */
    protected array $errors = [];
    
    /**
     * Create a new model instance
     * 
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->syncOriginal();
    }
    
    /**
     * Fill the model with an array of attributes
     * 
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Determine if the given attribute can be filled
     * 
     * @param string $key
     * @return bool
     */
    public function isFillable(string $key): bool
    {
        // If no fillable attributes defined, allow all
        if (empty($this->fillable)) {
            return true;
        }
        
        return in_array($key, $this->fillable);
    }
    
    /**
     * Set a given attribute on the model
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        
        return $this;
    }
    
    /**
     * Get an attribute from the model
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
        
        return $default;
    }
    
    /**
     * Check if an attribute exists
     * 
     * @param string $key
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
    
    /**
     * Remove an attribute
     * 
     * @param string $key
     * @return $this
     */
    public function removeAttribute(string $key): self
    {
        unset($this->attributes[$key]);
        
        return $this;
    }
    
    /**
     * Get all of the model's attributes
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Sync the original attributes with the current
     * 
     * @return $this
     */
    public function syncOriginal(): self
    {
        $this->original = $this->attributes;
        
        return $this;
    }
    
    /**
     * Get the model's original attribute values
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getOriginal(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->original;
        }
        
        return $this->original[$key] ?? $default;
    }
    
    /**
     * Determine if the model has changed since it was last retrieved
     * 
     * @param string|null $key
     * @return bool
     */
    public function isDirty(?string $key = null): bool
    {
        if ($key === null) {
            return $this->attributes != $this->original;
        }
        
        if (!array_key_exists($key, $this->original)) {
            return array_key_exists($key, $this->attributes);
        }
        
        return isset($this->attributes[$key]) && 
               $this->attributes[$key] !== $this->original[$key];
    }
    
    /**
     * Apply validation rules to the model attributes
     * 
     * @param array|null $rules
     * @return bool
     */
    public function validate(?array $rules = null): bool
    {
        $this->errors = [];
        $rules = $rules ?? $this->rules;
        
        foreach ($rules as $attribute => $rule) {
            $ruleParts = explode('|', $rule);
            
            foreach ($ruleParts as $rulePart) {
                $params = [];
                
                if (strpos($rulePart, ':') !== false) {
                    list($ruleName, $ruleParams) = explode(':', $rulePart, 2);
                    $params = explode(',', $ruleParams);
                } else {
                    $ruleName = $rulePart;
                }
                
                $value = $this->getAttribute($attribute);
                $methodName = 'validate' . ucfirst($ruleName);
                
                if (method_exists($this, $methodName)) {
                    $result = $this->$methodName($attribute, $value, $params);
                    
                    if ($result !== true) {
                        if (!isset($this->errors[$attribute])) {
                            $this->errors[$attribute] = [];
                        }
                        
                        $this->errors[$attribute][] = $result;
                    }
                }
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Validate that a field is required
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateRequired(string $attribute, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return "The {$attribute} field is required.";
        }
        
        return true;
    }
    
    /**
     * Validate that a field is a valid email
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateEmail(string $attribute, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$attribute} field must be a valid email address.";
        }
        
        return true;
    }
    
    /**
     * Validate that a field has a minimum length
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateMin(string $attribute, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $min = (int) ($params[0] ?? 0);
        
        if (is_string($value) && mb_strlen($value) < $min) {
            return "The {$attribute} field must be at least {$min} characters.";
        }
        
        if (is_numeric($value) && $value < $min) {
            return "The {$attribute} field must be at least {$min}.";
        }
        
        return true;
    }
    
    /**
     * Validate that a field has a maximum length
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateMax(string $attribute, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $max = (int) ($params[0] ?? 0);
        
        if (is_string($value) && mb_strlen($value) > $max) {
            return "The {$attribute} field must not exceed {$max} characters.";
        }
        
        if (is_numeric($value) && $value > $max) {
            return "The {$attribute} field must not exceed {$max}.";
        }
        
        return true;
    }
    
    /**
     * Validate that a field matches a regular expression
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateRegex(string $attribute, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $pattern = $params[0] ?? '';
        
        if (!preg_match($pattern, $value)) {
            return "The {$attribute} field format is invalid.";
        }
        
        return true;
    }
    
    /**
     * Validate that a field has a value in a list
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool|string
     */
    protected function validateIn(string $attribute, $value, array $params = [])
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (!in_array($value, $params)) {
            return "The selected {$attribute} is invalid.";
        }
        
        return true;
    }
    
    /**
     * Get or set an attribute value using property syntax
     * 
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Set an attribute value using property syntax
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value)
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Check if an attribute exists using property syntax
     * 
     * @param string $key
     * @return bool
     */
    public function __isset(string $key)
    {
        return $this->hasAttribute($key);
    }
    
    /**
     * Remove an attribute using property syntax
     * 
     * @param string $key
     * @return void
     */
    public function __unset(string $key)
    {
        $this->removeAttribute($key);
    }
    
    /**
     * Convert the model to an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }
    
    /**
     * Convert the model to JSON
     * 
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->toArray(), $options);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FrameworkException('Error converting model to JSON: ' . json_last_error_msg());
        }
        
        return $json;
    }
    
    /**
     * Convert the model to a string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
