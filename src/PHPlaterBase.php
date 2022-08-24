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

    /**
     * @var array<string|int, mixed>
     */
    public array $plates = [];
    public string $content = '';
    public string $result = '';
    public string $root = '';
    public string $extension = '';
    public bool $many = false;

    /**
     * @var array<string, callable>
     */
    public array $filters = [];

    /**
     * @var array<string, object>
     */
    public array $instances = [];

    /**
     * @var array<int, string>
     */
    public static array $tags = [];

    /**
     * @var array<string|int, PHPlater>
     */
    public static array $function_instances = [];

    /**
     * Creates the object and initializes it
     *
     * @access public
     */
    public function __construct(PHPlater $phplater) {
        $this->setCore($phplater);
    }

    /**
     * Get the instance of the object on demand
     *
     * @access public
     * @param  string $const get the current instance of the corresponding class
     */
    public function getPHPlaterObject(string $const): object {
        if(!isset($this->instances[$const])){
            $this->instances[$const] = new $const($this);
        }
        return $this->instances[$const];
    }

    /**
     * Get the core object
     *
     * @access protected
     * @return PHPlater Returns core object
     */
    protected function getCore(): PHPlater {
        return $this->core ?? new PHPlater();
    }

    /**
     * Set the core object
     *
     * @access protected
     * @param  PHPlater $phplater the core PHPlater object
     * @return void
     */
    protected function setCore(PHPlater $phplater): void {
        $this->core = $phplater;
    }

    /**
     * Will manage the input so that if it is json it converted to an array, otherwise input is returned
     *
     * @access public
     * @param  mixed $data If valid json, return array
     * @return array Returns valid content as an array if it is an json
     */
    public static function ifJsonToArray(mixed $data): string|object|array {
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
        $tag_before = self::getTag($before);
        $tag_after = self::getTag($after);
        $delimiter = self::getTag(self::TAG_DELIMITER);
        return $delimiter . $tag_before . $pattern . $tag_after . $delimiter;
    }

    /**
     * Get tag by tag constant
     *
     * @access public
     * @param  int $tag_constant The constant to get tag from
     * @return string The tag
     */
    public static function getTag(int $tag_constant): string {
        return self::$tags[$tag_constant] ?? '';
    }

    /**
     * Set tag by a constant
     *
     * @access public
     * @param  int $tag_constant The constant to set tag with
     * @param  string $tag The tag string
     * @return void
     */
    public static function setTag(int $tag_constant, string $tag): void {
        if ($tag_constant === self::TAG_DELIMITER) {
            if (strlen($tag) > 1) {
                throw new RuleBrokenError('Preg delimiter can not be over 1 in length.');
            } else if (ctype_alnum($tag) || $tag === '\\') {
                throw new RuleBrokenError('Preg Delimiter can not be alphanumeric or backslash.');
            }
        }
        self::$tags[$tag_constant] = $tag;
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
    public static function setTagsVariables(string $before, string $after): void {
        self::setTags([
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
    public static function setTagsConditionals(string $before, string $after): void {
        self::setTags([
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
    public static function setTagsList(string $before, string $after): void {
        self::setTags([
            self::TAG_LIST_BEFORE => preg_quote($before),
            self::TAG_LIST_AFTER => preg_quote($after)
        ]);
    }

    /**
     * Set all tags you want in one method
     *
     * @access public
     * @param array<int, string> $tags The array of all the tags to set
     * @return void
     */
    public static function setTags(array $tags): void {
        foreach ($tags as $const => $tag) {
            self::setTag($const, $tag);
        }
    }

    /**
     * Get all tags
     *
     * @access public
     * @return array<string> All the tags
     */
    public static function getTags(): array {
        return self::$tags;
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