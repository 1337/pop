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
        /** @var $filters array */
        foreach((array) $filters as $condition => $value) {
            if (strpos($condition, '__') === false) {
                // format foo, assuming foo__eq
                $condition = $condition . '__eq';
            }
            // format foo__bar
            $decomposition = explode('__', $condition);
            $key_name = $decomposition[0];  // e.g. pk
            $key_comp = $decomposition[1];  // e.g. eq

            foreach ((array) $matches as $idx => $model) {
                $model_key_val = $model->$key_name;
                if ($model_key_val === null) {
                    // unless the query is actually looking for == null,
                    // this is a failed model for not having that attribute
                    if ($key_comp === 'eq' && $value === null) {
                        // well fine
                        break;
                    } else {
                        // no such attribute, waste no time
                        unset($matches[$idx]);
                    }
                }
                // conditions more or less the ones worth implementing in
                // https://docs.djangoproject.com/en/dev/ref/models/querysets/
                switch ($key_comp) {
                    case 'eq':
                        if ($model_key_val === $value) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'eqv':
                        // nonstandard (added it just for shits)
                        if ($model_key_val == $value) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'neq':
                        if ($model_key_val !== $value) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'lt':
                        if ($model_key_val < $value) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'lte':
                        if ($model_key_val <= $value) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'gt':
                        if ($model_key_val > $value) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'gte':
                        if ($model_key_val >= $value) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'in':
                        // '5' in [1,2,3,4,5]
                        if (in_array($model_key_val, $value)) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'contains':
                        // [1,2,3,4,5] contains '5'
                        if (in_array($value, $model_key_val)) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'startswith':
                        // 'abc'__startswith: 'a'
                        if (strpos($model_key_val, $value) === 0) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'endswith':
                        // 'abc'__startswith: 'c'
                        if (substr($model_key_val, -strlen($value))
                            === $value) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                    case 'range':
                        // 5 range (1, 10)
                        if ($model_key_val >= $value[0] &&
                            $model_key_val <= $value[1]) {
                            break 2;  // 2? http://www.php.net/break
                        }
                        break;
                }
                // if nothing passes, reject the model
                unset($matches[$idx]);
            }
        }
        return $matches;
    }
}