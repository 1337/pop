<?php

namespace Pop\lib;

/**
 * Class PathResolver
 *
 * str(new ThisThing($path)) is always a path.
 */
class PathResolver {
    private $_path = '';

    /**
     * @param        $path
     * @param string $file  see __invoke.
     */
    public function __construct($path, $file='') {
        $this->_path = $path;
        if ($file !== '') {
            $this->_path .= $file;
        }
    }

    /**
     * (string)$this is always the path.
     * @return string
     */
    public function __toString() {
        return $this->_path;
    }

    /**
     * If you asked for $this->exists, then it calls $this->_get_exists().
     * @param $property
     * @return mixed
     */
    public function __get($property) {
        $method = '_get_' . $property;
        return $this->$method();
    }

    /**
     * $instance($path) appends $path to the instance's internal path.
     * $path does not need to be prefixed by a slash.
     *
     * @param $path
     * @return PathResolver
     */
    public function __invoke($path) {
        if (substr($path, 0, 1) === DIRECTORY_SEPARATOR) {
            $path = substr($path, 1);
        }
        return new self(self::_format_path($this->_path) . $path);
    }

    /**
     * @param $path
     * @return string that always ends in a slash (DIRECTORY_SEPARATOR).
     */
    private static function _format_path($path) {
        if (substr($path, -1) === DIRECTORY_SEPARATOR) {
            return $path;
        }
        return $path . DIRECTORY_SEPARATOR;
    }

    /**
     * The parent path. with a slash.
     * @return PathResolver
     */
    private function _get_parent() {
        return new self(self::_format_path(dirname($this->_path)));
    }

    /**
     * @returns bool
     */
    private function _get_exists() {
        // note: file_exists returns true for directories as well
        if (file_exists($this->_path)) {
            return true;
        }
        return false;
    }
}