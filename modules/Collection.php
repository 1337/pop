<?php

    /**
     * Class Collection
     * An object with references to multiple models, via attribute `models`.
     */
    class Collection {
        public $models = array();

        /**
         * @param Array $models
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

        /**
         * @param array $filters  something in the format {"age__eq": "50"}
         * @return array          models that pass all these filters.
         * @throws UnexpectedValueException
         */
        public function filter($filters) {
            $matches = $this->models;

            if (!is_array($filters)) {
                throw new UnexpectedValueException('Filters must be array');
            }
            foreach($filters as $condition => $value) {
                if (strpos($condition, '__') === false) {
                    // format foo, assuming foo__eq
                    $condition = $condition . '__eq';
                }
                // format foo__bar
                $decomposition = explode('__', $condition);
                $key_name = $decomposition[0];  // e.g. pk
                $key_comp = $decomposition[1];  // e.g. eq

                foreach ((array) $matches as $idx => $model) {
                    if ($model->$key_name === null) {
                        // unless the query is actually looking for == null,
                        // this is a failed model for not having that attribute
                        if ($key_comp === 'eq' && $value === null) {

                        } else {
                            // no such attribute, waste no time
                            unset($matches[$idx]);
                        }
                    }
                }
            }
            return $matches;
        }
    }