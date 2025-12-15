<?php
/**
 * Dependency Injection Container
 * IoC container for better dependency management and testability
 * 
 * @package SLPA\DI
 * @version 1.0.0
 */

class Container {
    private static $instance = null;
    private $bindings = [];
    private $instances = [];
    private $aliases = [];
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Bind a class or interface to a concrete implementation
     * 
     * @param string $abstract Class or interface name
     * @param mixed $concrete Closure, class name, or instance
     * @param bool $singleton Whether to create a singleton
     */
    public function bind($abstract, $concrete = null, $singleton = false) {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
        
        return $this;
    }
    
    /**
     * Register a singleton binding
     */
    public function singleton($abstract, $concrete = null) {
        return $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Register an existing instance as a singleton
     */
    public function instance($abstract, $instance) {
        $this->instances[$abstract] = $instance;
        return $this;
    }
    
    /**
     * Register an alias for a class
     */
    public function alias($alias, $abstract) {
        $this->aliases[$alias] = $abstract;
        return $this;
    }
    
    /**
     * Resolve a class from the container
     * 
     * @param string $abstract Class or interface name
     * @param array $parameters Constructor parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = []) {
        // Resolve alias
        if (isset($this->aliases[$abstract])) {
            $abstract = $this->aliases[$abstract];
        }
        
        // Return existing singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Get binding
        $concrete = $this->getConcrete($abstract);
        
        // Build the object
        $object = $this->build($concrete, $parameters);
        
        // Store singleton
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['singleton']) {
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    /**
     * Get concrete implementation
     */
    private function getConcrete($abstract) {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        
        return $abstract;
    }
    
    /**
     * Build an instance of the concrete implementation
     */
    private function build($concrete, array $parameters = []) {
        // If closure, execute it
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
        
        // If already an instance, return it
        if (is_object($concrete)) {
            return $concrete;
        }
        
        // Reflection to auto-wire dependencies
        $reflector = new ReflectionClass($concrete);
        
        // Check if class is instantiable
        if (!$reflector->isInstantiable()) {
            throw new Exception("Class $concrete is not instantiable");
        }
        
        $constructor = $reflector->getConstructor();
        
        // No constructor, instantiate without dependencies
        if ($constructor === null) {
            return new $concrete;
        }
        
        // Get constructor parameters
        $dependencies = $this->resolveDependencies(
            $constructor->getParameters(),
            $parameters
        );
        
        return $reflector->newInstanceArgs($dependencies);
    }
    
    /**
     * Resolve constructor dependencies
     */
    private function resolveDependencies(array $parameters, array $primitives = []) {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            
            // If parameter has a class type hint
            if ($dependency !== null) {
                $dependencies[] = $this->make($dependency->name);
            }
            // If parameter is provided in primitives
            elseif (isset($primitives[$parameter->name])) {
                $dependencies[] = $primitives[$parameter->name];
            }
            // If parameter has default value
            elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            }
            else {
                throw new Exception("Cannot resolve parameter {$parameter->name}");
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Check if abstract is bound
     */
    public function bound($abstract) {
        return isset($this->bindings[$abstract]) || 
               isset($this->instances[$abstract]) || 
               isset($this->aliases[$abstract]);
    }
    
    /**
     * Remove binding
     */
    public function forget($abstract) {
        unset($this->bindings[$abstract], $this->instances[$abstract], $this->aliases[$abstract]);
    }
    
    /**
     * Call a method with auto-resolved dependencies
     */
    public function call($callback, array $parameters = []) {
        if (is_string($callback) && strpos($callback, '@') !== false) {
            $callback = explode('@', $callback);
        }
        
        if (is_array($callback)) {
            list($class, $method) = $callback;
            $instance = is_object($class) ? $class : $this->make($class);
            $reflector = new ReflectionMethod($instance, $method);
        } elseif ($callback instanceof Closure) {
            $reflector = new ReflectionFunction($callback);
            $instance = null;
        } else {
            throw new Exception("Invalid callback");
        }
        
        $dependencies = $this->resolveDependencies(
            $reflector->getParameters(),
            $parameters
        );
        
        if ($instance !== null) {
            return $reflector->invokeArgs($instance, $dependencies);
        }
        
        return $reflector->invokeArgs($dependencies);
    }
    
    /**
     * Get all bindings
     */
    public function getBindings() {
        return $this->bindings;
    }
    
    /**
     * Clear all bindings and instances
     */
    public function flush() {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
    }
}

/**
 * Helper function for container
 */
function app($abstract = null, array $parameters = []) {
    if ($abstract === null) {
        return Container::getInstance();
    }
    
    return Container::getInstance()->make($abstract, $parameters);
}
