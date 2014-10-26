<?php

namespace Pop;

/**
 * Class Prototypable
 * Allows methods to be attached to a Model.
 *
 * @package Pop
 */
trait Prototypable {
    protected $methods = [];

    /**
     * $methods is an array storing callbacks to functions.
     *
     * if this object is registered with extra, object-bound methods,
     * it will be called like this.
     * if you want to register methods for all instances of the same
     * class, then you might want to write a private function.
     *
     * @param string $name: this function is not actually public.
     * @param array  $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $args) {
        if (!isset($this->methods[$name])) {
            throw new \Exception("Method $name not registered");
        }    
        return call_user_func_array($this->methods[$name], $args);
    }
}