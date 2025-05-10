<?php

namespace Framework\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;
use ReflectionException;
use Framework\Exceptions\FrameworkException;

/**
 * Service Container for dependency injection
 * 
 * This container registers, resolves and manages service instances
 * throughout the application lifecycle.
 */
class Container
{
    /**
     * The container's bindings.
     *
     * @var array
     */
    protected array $bindings = [];
    
    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected array $instances = [];
    
    /**
     * Register a binding with the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?: $abstract,
            'shared' => $shared,
        ];
    }
    
    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Register an existing instance as a shared binding.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance(string $abstract, $instance): mixed
    {
        $this->instances[$abstract] = $instance;
        
        return $instance;
    }
    
    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws FrameworkException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        // If we have an instance in the container, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Get the concrete implementation
        $concrete = $this->getConcrete($abstract);
        
        // If the type is a concrete implementation, resolve it
        $object = $this->build($concrete, $parameters);
        
        // If the binding is shared, store the instance
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    /**
     * Get the concrete implementation for a given abstract.
     *
     * @param string $abstract
     * @return mixed
     */
    protected function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        
        return $abstract;
    }
    
    /**
     * Determine if the given abstract has a shared instance.
     *
     * @param string $abstract
     * @return bool
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->bindings[$abstract]['shared']) &&
               $this->bindings[$abstract]['shared'] === true;
    }
    
    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param mixed $concrete
     * @param array $parameters
     * @return mixed
     * @throws FrameworkException
     */
    protected function build($concrete, array $parameters = []): mixed
    {
        // If the concrete is a Closure, just execute it and return the result
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
        
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new FrameworkException("Target class [$concrete] does not exist: " . $e->getMessage());
        }
        
        // If the class cannot be instantiated, throw an exception
        if (!$reflector->isInstantiable()) {
            throw new FrameworkException("Target [$concrete] is not instantiable.");
        }
        
        $constructor = $reflector->getConstructor();
        
        // If there are no constructor parameters, just return a new instance
        if (is_null($constructor)) {
            return new $concrete;
        }
        
        // Get the constructor parameters
        $dependencies = $constructor->getParameters();
        
        // If there are no dependencies, just return a new instance
        if (empty($dependencies)) {
            return new $concrete;
        }
        
        // Build the list of parameters
        $resolvedDependencies = $this->resolveDependencies($dependencies, $parameters);
        
        // Create a new instance with the resolved dependencies
        return $reflector->newInstanceArgs($resolvedDependencies);
    }
    
    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param array $dependencies
     * @param array $parameters
     * @return array
     * @throws FrameworkException
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];
        
        foreach ($dependencies as $dependency) {
            // If the parameter is in the given parameters, use it
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];
                continue;
            }
            
            // If the parameter is a class, resolve it from the container
            $results[] = $this->resolveClass($dependency);
        }
        
        return $results;
    }
    
    /**
     * Resolve a class based dependency from the container.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws FrameworkException
     */
    protected function resolveClass(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        
        // If the parameter doesn't have a type hint or is a built-in type, 
        // and is optional, use the default value
        if (!$type || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw new FrameworkException(
                "Unresolvable dependency: $parameter in class {$parameter->getDeclaringClass()->getName()}"
            );
        }
        
        try {
            // Use the ReflectionNamedType API which works in PHP 7.4+
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : (string)$type;
            return $this->make($typeName);
        } catch (FrameworkException $e) {
            // If we can't resolve the class but the parameter is optional, 
            // use the default value
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw $e;
        }
    }
    
    /**
     * Check if a binding exists in the container.
     *
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
    
    /**
     * Remove a binding from the container.
     *
     * @param string $abstract
     * @return void
     */
    public function remove(string $abstract): void
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }
    
    /**
     * Call a method on an object with dependency injection.
     *
     * @param mixed $callback
     * @param array $parameters
     * @return mixed
     * @throws FrameworkException
     */
    public function call($callback, array $parameters = []): mixed
    {
        if (is_array($callback)) {
            // If the first element is a string (class name), instantiate it
            if (is_string($callback[0])) {
                $callback[0] = $this->make($callback[0]);
            }
            
            $reflectionMethod = new \ReflectionMethod($callback[0], $callback[1]);
            $dependencies = $reflectionMethod->getParameters();
            
            $parameters = $this->resolveDependencies($dependencies, $parameters);
            
            return $reflectionMethod->invokeArgs($callback[0], $parameters);
        }
        
        if ($callback instanceof Closure) {
            $reflectionFunction = new \ReflectionFunction($callback);
            $dependencies = $reflectionFunction->getParameters();
            
            $parameters = $this->resolveDependencies($dependencies, $parameters);
            
            return $reflectionFunction->invokeArgs($parameters);
        }
        
        if (is_string($callback) && strpos($callback, '::') !== false) {
            list($class, $method) = explode('::', $callback);
            return $this->call([$class, $method], $parameters);
        }
        
        if (is_string($callback) && method_exists($callback, '__invoke')) {
            $instance = $this->make($callback);
            return $this->call([$instance, '__invoke'], $parameters);
        }
        
        throw new FrameworkException("Invalid callback provided to container->call()");
    }
}
