<?php

    /**
     * All Models must implement these methods.
     */
    interface ModelInterface {
        public function __get($property);
        public function __set($property);
    }