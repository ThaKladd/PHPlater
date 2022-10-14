<?php

/**
 * All the classes in PHPLater
 *
 * @author  John Larsen
 * @license MIT
 */
enum ClassString: string {

    case BASE = 'PHPlaterBase';
    case CORE = 'PHPlater';
    case VARIABLE = 'PHPlaterVariable';
    case LISTS = 'PHPlaterList';
    case CONDITIONAL = 'PHPlaterConditional';
    case FILTER = 'PHPlaterFilter';
    case KEY = 'PHPlaterKey';
    case INCLUDE_FILE = 'PHPlaterInclude';

    /**
     * Caches the patterns, to reduce unnecessary redundancy
     *
     * @access public
     * @return string
     */
    public function pattern() {
        if (!isset(PHPlaterBase::$instances[$this->value]['pattern'])) {
            PHPlaterBase::$instances[$this->value]['pattern'] = $this->value::pattern();
        }
        return PHPlaterBase::$instances[$this->value]['pattern'];
    }

    /**
     * Get the instance of the core on demand
     *
     * @access public
     * @return object Object representing the class string
     */
    public function object(): object {
        if (!isset(PHPlaterBase::$instances[$this->value]['object'])) {
            PHPlaterBase::$instances[$this->value]['object'] = new $this->value();
        }
        return PHPlaterBase::$instances[$this->value]['object'];
    }

}
