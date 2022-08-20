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
use Error\RuleBrokenError;

class PHPlaterBase {

    public static $root = '';
    public static $extension = '.tpl';
    protected $core = null;

    /**
     * All data is managed within this one property array.
     * Defaults are set in constructor, and they can be hidden inside array.
     *
     * @var array $data {\
     *  @type string $content Content of the template, either from string or file\
     *  @type string $result Where the result of the render is stored\
     *  @type array $plates Array of key value pairs that is the structure for the variables in the template. Can be multidimensional\
     *  @type array $tags Array all the tags set, mapped to the constant\
     * }
     */
    protected array $data = [
        'content' => '',
        'result' => '',
        'plates' => []
    ];
    
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
        return $this->core;
    }

    /**
     * Get and set the root folder of templates
     *
     * @access public
     * @param  string $location Location to root folder of templates
     * @return string Returns location to templates
     */
    public static function root(?string $location = null): string {
        if(is_null($location)){
            return self::$root;
        }
        return self::$root = $location;
    }

    /**
     * Get and set the template extension, if set, the extension is not needed to be used
     * Default: .tpl
     *
     * @access public
     * @param  string $extension of the template file
     * @return string Returns extension of the template file
     */
    public static function extension(?string $extension = null): string {
        if(is_null($extension)){
            return self::$extension;
        }
        return self::$extension = $extension;
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
     * Stub for pattern implementation
     *
     * @access public
     * @return string
     */
    public function pattern(): string {
        return '';
    }

    /**
     * Stub for find implementation
     *
     * @access public
     * @param  array
     * @return string
     */
    public function find(array $match): string {
        return '';
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
        return $this->core()->get(PHPlater::CLASS_TAG)->tag($tag_constant, $tag);
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
        return $this->core()->get(PHPlater::CLASS_TAG)->tagsVariables($before, $after);
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
        return $this->core()->get(PHPlater::CLASS_TAG)->tagsConditionals($before, $after);
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
        return $this->core()->get(PHPlater::CLASS_TAG)->tagsList($before, $after);
    }

    public function debug($value){
        echo PHP_EOL.'DEBUG > '.print_r($value, true).' < DEBUG'.PHP_EOL;
    }
}