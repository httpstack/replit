<?php

namespace App\Models;

use Framework\Model\BaseModel;

/**
 * Example Model
 * 
 * An example model to demonstrate the model system
 */
class ExampleModel extends BaseModel
{
    /**
     * The model's fillable attributes
     * 
     * @var array
     */
    protected array $fillable = [
        'name',
        'email',
        'description',
        'active',
    ];
    
    /**
     * The model's validation rules
     * 
     * @var array
     */
    protected array $rules = [
        'name' => 'required|min:3|max:255',
        'email' => 'required|email',
        'description' => 'max:1000',
        'active' => 'in:0,1',
    ];
    
    /**
     * Get a formatted name value
     * 
     * @return string
     */
    public function getFormattedName(): string
    {
        return ucwords($this->getAttribute('name', ''));
    }
    
    /**
     * Check if the model is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getAttribute('active', false);
    }
    
    /**
     * Set the active status
     * 
     * @param bool $active
     * @return $this
     */
    public function setActive(bool $active): self
    {
        return $this->setAttribute('active', $active ? 1 : 0);
    }
    
    /**
     * Create a new example model instance from request data
     * 
     * @param array $requestData
     * @return self
     */
    public static function fromRequest(array $requestData): self
    {
        $model = new static($requestData);
        
        // Ensure active is a boolean
        if (isset($requestData['active'])) {
            $model->setActive((bool) $requestData['active']);
        }
        
        return $model;
    }
}
