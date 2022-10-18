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

include_once 'Enum\Tag.php';
include_once 'Enum\ClassString.php';

class PHPlaterBase {

    public static array $hold = ['include' => [], 'block' => [], 'blocks' => []];
    public static array $content_cache = [];
    public static array $instances = [];
    public static bool $changed_tags = true;
    public static bool $cache = false;

    /**
     * @var array<string, callable>
     */
    public array $filters = [];

    /**
     * @var array<int, string>
     */
    public static array $tags = [];

    /**
     * @var array<string|int, PHPlater>
     */
    public static array $function_instances = [];

    /**
     * Set cache true of false. Default: false
     *
     * @access public
     * @param bool $toggle Toggle cache true or false
     * @return PHPLater
     */
    public function setCache(bool $toggle): PHPlater {
        self::$cache = $toggle;
        return $this;
    }

    /**
     * Get if cache is on
     *
     * @access public
     * @return bool
     */
    public function getCache(): bool {
        return self::$cache;
    }

    /**
     * Cache data into hash
     *
     * @access private
     * @param  string $key Key or hash of the data
     * @param  context $context To store within the key
     * @param  string|array $data Data to store
     * @return string The stored data or data that is set
     */
    protected function cache(string $key, string $context = 'data', array|string $data = ''): array|string {
        $hash = hash('xxh3', $key);
        if ($this->getCache()) {
            if ($data) {
                self::$content_cache[$hash][$context] = $data;
            } else if (isset(self::$content_cache[$hash][$context])) {
                $data = self::$content_cache[$hash][$context];
            }
        }
        return $data;
    }

    /**
     * Caches the patterns, to reduce unnecessary redundancy
     * TODO: This may break, as patterns for objects may change and this is static - it does not because tags are set usually only once
     * Fix can be done by adding a tag sum and changing tags to be binary, but is it worth it?
     *
     * @access protected
     * @param  string $class_name The class name to object
     * @param  object $class The object to get pattern from
     * @return string
     */
    protected static function patternCache(string $class_name): string {
        if (!isset(self::$pattern_cache[$class_name])) {
            self::$pattern_cache[$class_name] = $class_name::pattern();
        }
        return self::$pattern_cache[$class_name];
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
            $data = is_array($array) ? $array : $data;
        }
        return $data;
    }

    /**
     * Get the pattern used to fetch all the variable tags in the template

     * @access protected
     * @param  enum $before The before tag
     * @param  string $pattern The pattern
     * @param  enum $after The after tag
     * @return string The pattern for preg_replace_callback
     */
    protected static function buildPattern(Tag $before, string $pattern, Tag $after): string {
        $tag_before = $before->get();
        $tag_after = $after->get();
        $delimiter = Tag::DELIMITER->get(true);
        return $delimiter . $tag_before . $pattern . $tag_after . $delimiter;
    }

    /**
     * Set both template variable tags in one method (default {{ and }})
     *
     * @access public
     * @param  string $before Tag before variable in template
     * @param  string $after Tag after variable in template
     * @return void
     */
    public static function setTagsVariables(string $before, string $after): void {
        Tag::BEFORE->set($before);
        Tag::AFTER->set($after);
    }

    /**
     * Set both conditional tags in one method (default (( and )))
     *
     * @access public
     * @param  string $before Tag before conditional template in template
     * @param  string $after Tag after conditional template in template
     * @return void
     */
    public static function setTagsConditionals(string $before, string $after): void {
        Tag::CONDITIONAL_BEFORE->set($before);
        Tag::CONDITIONAL_AFTER->set($after);
    }

    /**
     * Set both list tags in one method (default [[ and ]])
     *
     * @access public
     * @param  string $before Tag before list template in template
     * @param  string $after Tag after list template in template
     * @return void
     */
    public static function setTagsList(string $before, string $after): void {
        Tag::LIST_BEFORE->set($before);
        Tag::LIST_AFTER->set($after);
    }

    /**
     * Set both block tags in one method (default >> and <<)
     *
     * @access public
     * @param  string $before Tag before list template in template
     * @param  string $after Tag after list template in template
     * @return void
     */
    public static function setTagsBlock(string $before, string $after): void {
        Tag::BLOCK_BEFORE->set($before);
        Tag::BLOCK_AFTER->set($after);
    }

    /**
     * Get all tags
     *
     * @access public
     * @param  bool $raw The raw version of the tag
     * @return array<string> All the tags
     */
    public static function getTags(bool $raw = false): array {
        return self::$tags[$raw] ?? [];
    }

    /**
     * Quick debugging
     *
     * @access public
     * @param  mixed $value Whatever value to debug
     * @return void
     */
    public static function debug(mixed $value): void {
        echo PHP_EOL . 'DEBUG &gt; <pre>' . print_r($value, true) . '</pre> &lt; DEBUG' . PHP_EOL;
    }

}
