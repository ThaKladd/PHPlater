<?php

/**
 * The PHPlaterBase class
 *
 * PHPlater objects extends this class to get access to its features
 *
 * @author  John Larsen
 * @license MIT
 */
use Error\RuleBrokenError;

class PHPlaterBase {

    const CLASS_BASE = 'PHPlaterBase';
    const CLASS_CORE = 'PHPlater';
    const CLASS_VARIABLE = 'PHPlaterVariable';
    const CLASS_LIST = 'PHPlaterList';
    const CLASS_CONDITIONAL = 'PHPlaterConditional';
    const CLASS_FILTER = 'PHPlaterFilter';
    const CLASS_KEY = 'PHPlaterKey';

    const TAG_BEFORE = 0;
    const TAG_AFTER = 1;
    const TAG_LIST_BEFORE = 2;
    const TAG_LIST_AFTER = 3;
    const TAG_LIST_KEY = 4;
    const TAG_CONDITIONAL_BEFORE = 5;
    const TAG_CONDITIONAL_AFTER = 6;
    const TAG_IF = 7;
    const TAG_ELSE = 8;
    const TAG_ARGUMENT = 9;
    const TAG_ARGUMENT_LIST = 10;
    const TAG_CHAIN = 11;
    const TAG_FILTER = 12;
    const TAG_DELIMITER = 13;

    protected ?PHPlater $core = null;

    /**
     * All data is managed within this one property array.
     * Defaults are set in constructors
     */
    protected array $data = [];
    protected array $instances = [];
    public static array $tags = [];
    public static array $function_instances = [];

    /**
     * Creates the object and initializes it
     *
     * @access public
     */
    public function __construct(PHPlater $phplater) {
        $this->core($phplater);
    }

    /**
     * Get the instance of the object on demand
     *
     * @access public
     * @param  string $const get the current instance of the corresponding class
     */
    public function get(string $const): object {
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
     * @param  object|array|string|int|float|bool|null $value If value other than null, it is stored in the key
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
     * @param  PHPlater $phplater the core phplater object
     * @return PHPlater Returns core object
     */
    protected function core(?PHPlater $phplater = null): PHPlater {
        if(!is_null($phplater)){
            $this->core = $phplater;
        }
        return $this->core ?? new PHPlater();
    }

    /**
     * Will manage the input so that if it is json it converted to an array, otherwise input is returned
     *
     * @access public
     * @param  mixed $data If valid json, return array
     * @return mixed Returns valid content as an array if it is an json
     */
    public static function ifJsonToArray(mixed $data): mixed {
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
    protected static function buildPattern(int $before, string $pattern, int $after): string {
        $tag_before = self::tag($before);
        $tag_after = self::tag($after);
        $delimiter = self::tag(self::TAG_DELIMITER);
        return $delimiter . $tag_before . $pattern . $tag_after . $delimiter;
    }

    /**
     * Set or get tag by a constant
     *
     * @access public
     * @param  int $tag_constant The constant to set or get tag with
     * @param  ?string $tag The tag string, if you want to set the tag
     * @return ?string The current object if a set, the string tag if it is get
     */
    public static function tag(int $tag_constant, ?string $tag = null): ?string {
        if($tag === null){
            return self::$tags[$tag_constant];
        } else {
            if ($tag_constant === self::TAG_DELIMITER) {
                if (strlen($tag) > 1) {
                    throw new RuleBrokenError('Preg delimiter can not be over 1 in length.');
                } else if (ctype_alnum($tag) || $tag === '\\') {
                    throw new RuleBrokenError('Preg Delimiter can not be alphanumeric or backslash.');
                }
            }
            self::$tags[$tag_constant] = $tag;
        }
        return null;
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
     * @return void
     */
    public static function tagsVariables(string $before, string $after): void {
        self::tags([
            self::TAG_BEFORE => preg_quote($before),
            self::TAG_AFTER => preg_quote($after)
        ]);
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
     * @return void
     */
    public static function tagsConditionals(string $before, string $after): void {
        self::tags([
            self::TAG_CONDITIONAL_BEFORE => preg_quote($before),
            self::TAG_CONDITIONAL_AFTER => preg_quote($after)
        ]);
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
     * @return void
     */
    public static function tagsList(string $before, string $after): void {
        self::tags([
            self::TAG_LIST_BEFORE => preg_quote($before),
            self::TAG_LIST_AFTER => preg_quote($after)
        ]);
    }

    /**
     * Set all tags you want in one method or get all tags that are set
     *
     * @access public
     * @param  ?array $tags an array with constant as key, and tag as value
     * @return ?array The current object or an array with all the tags
     */
    public static function tags(?array $tags = null): ?array {
        if ($tags === null) {
            return self::$tags;
        }
        foreach ($tags as $const => $tag) {
            self::tag($const, $tag);
        }
        return null;
    }

    /**
     * Quick debugging
     *
     * @access public
     * @param  mixed $value Whatever value to debug
     * @return void
     */
    public static function debug(mixed $value): void {
        echo PHP_EOL.'DEBUG > <pre>'.print_r($value, true).'</pre> < DEBUG'.PHP_EOL;
    }
}