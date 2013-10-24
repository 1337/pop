<?php

    /**
     * Class Collection
     * An object with references to multiple models, via attribute `models`.
     */
    class Collection {
        public $models = array();

        /**
         * @param {Array} $models
         */
        public function __construct($models) {
            $this->models = $models;
        }

        /**
         * @return int
         */
        public function length() {
            return sizeof($this->models);
        }

        public function filter() {

        }
    }