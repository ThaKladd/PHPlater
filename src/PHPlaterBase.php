<?php

/**
 * The PHPlater class, a simple template engine.
 *
 * This class can either be used as is or extended by another object.
 * The musts is to add a template, and to map the variables there to the plates in this object, and then run render to return the result.
 *
 * @author  John Larsen
 * @license MIT
 */

class PHPlaterBase {

    const CLASS_BASE = 'PHPlaterBase';
    const CLASS_CORE = 'PHPlater';
    const CLASS_VARIABLE = 'PHPlaterVariable';
    const CLASS_LIST = 'PHPlaterList';
    const CLASS_CONDITIONAL = 'PHPlaterConditional';
    const CLASS_FILTER = 'PHPlaterFilter';
    const CLASS_TAG = 'PHPlaterTag';
    const CLASS_KEY = 'PHPlaterKey';

    protected $core = null;

    /**
     * All data is managed within this one property array.
     * Defaults are set in constructors
     */
    protected $data = [];
    protected $instances = [];

    /**
     * Creates the object and initializes it
     *
     * @access public
     */
    public function __construct(PHPLater $phplater) {
        $this->core($phplater);
    }

    /**
     * Get the instance of the object on demand
     *
     * @access public
     * @param  string $const get the current instance of the corresponding class
     */
    public function get(string $const){
        if(!isset($this->instances[$const])){
            $this->instances[$const] = new $const($this);
        }
        return $this->instances[$const];
    }
    
    /**
     * Quick shortcut for getting and setting data inside current object
     *
     * @access protected
     * @param  string $key The key where data is stored or gotten from
     * @param  mixed $value If value other than null, it is stored in the key
     * @return mixed Returns either the data stored in key or the current object
     */
    protected function getSet(string $key, object|array|string|int|float|bool|null $value = null): mixed {
        if ($value === null) {
            return $this->data[$key] ?? '';
        }
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get and set the core object
     *
     * @access protected
     * @param  PHPLater $phplater the core phplater object
     * @return PHPLater Returns core object
     */
    protected function core(?PHPLater $phplater = null): PHPLater {
        if(!is_null($phplater)){
            $this->core = $phplater;
        }
        return $this->core ?? $this;
    }

    /**
     * Will manage the input so that if it is json it converted to an array, otherwise input is returned
     *
     * @access public
     * @param  mixed $data If valid json, return array
     * @return mixed Returns valid content as an array if it is an json
     */
    public function ifJsonToArray(mixed $data): mixed {
        if (is_string($data)) {
            $array = json_decode($data, true);
            $data = is_array($array) && $array ? $array : $data;
        }
        return $data;
    }

    /**
     * Get the pattern used to fetch all the variable tags in the template
     *
     * @access protected
     * @return string The pattern for preg_replace_callback
     */
    protected function buildPattern(int $before, string $pattern, int $after): string {
        $tag_before = $this->tag($before);
        $tag_after = $this->tag($after);
        $delimiter = $this->tag(PHPlaterTag::TAG_DELIMITER);
        return $delimiter . $tag_before . $pattern . $tag_after . $delimiter;
    }
    
    /**
     * Set or get tag by a constant
     *
     * @access public
     * @param  string $tag_constant The constant to set or get tag with
     * @param  string $tag The tag string, if you want to set the tag
     * @return mixed The current object if a set, the string tag if it is get
     */
    public function tag(int $tag_constant, string|null $tag = null): string|PHPlaterTag {
        return $this->core()->get(self::CLASS_TAG)->tag($tag_constant, $tag);
    }

    /**
     * Set both template variable tags in one method
     *
     * Change tags if the current(default {{ and }}) tags are part of template
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  string $before Tag before variable in template
     * @param  string $after Tag after variable in template
     * @return self The current object
     */
    public function tagsVariables(string $before, string $after): PHPlaterTag {
        return $this->core()->get(self::CLASS_TAG)->tagsVariables($before, $after);
    }
   
    /**
     * Set both conditional tags in one method
     *
     * Change tags if the current(default (( and ))) tags are part of template
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  string $before Tag before conditional template in template
     * @param  string $after Tag after conditional template in template
     * @return self The current object
     */
    public function tagsConditionals(string $before, string $after): PHPlaterTag {
        return $this->core()->get(self::CLASS_TAG)->tagsConditionals($before, $after);
    }

    /**
     * Set both list tags in one method
     *
     * Change tags if the current(default [[ and ]]) tags are part of template
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  string $before Tag before list template in template
     * @param  string $after Tag after list template in template
     * @return self The current object
     */
    public function tagsList(string $before, string $after): PHPlaterTag {
        return $this->core()->get(self::CLASS_TAG)->tagsList($before, $after);
    }

    /**
     * Quick debugging
     *
     * @access public
     * @param  mixed $value Whatever value to debug
     * @return void
     */
    public function debug(mixed $value): void {
        echo PHP_EOL.'DEBUG > <pre>'.print_r($value, true).'</pre> < DEBUG'.PHP_EOL;
    }
}