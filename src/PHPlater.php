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
class PHPlater {

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

    /**
     * All data is managed within this one property array.
     * Defaults are set in constructor, and they can be hidden inside array.
     *
     * @var array $data {\
     *  @type string $content Content of the template, either from string or file\
     *  @type string $result Where the result of the render is stored\
     *  @type array $plates Array of key value pairs that is the structure for the variables in the template. Can be multidimensional\
     * }
     */
    private array $data = [
        'content' => '',
        'result' => '',
        'plates' => [],
        'tags' => []
    ];

    public function __construct() {
        $this->tagsVariables('{{', '}}');
        $this->tagsList('[[', ']]');
        $this->tagsConditionals('((', '))');
        $this->tag(self::TAG_IF, '??');
        $this->tag(self::TAG_ELSE, '::');
        $this->tag(self::TAG_LIST_KEY, '#');
        $this->tag(self::TAG_ARGUMENT, ':');
        $this->tag(self::TAG_ARGUMENT_LIST, ',');
        $this->tag(self::TAG_CHAIN, '.');
        $this->tag(self::TAG_FILTER, '|');
        $this->tag(self::TAG_DELIMITER, '|');
    }

    /**
     * Quick shortcut for getting and setting data inside current object
     *
     * @access private
     * @param  string $key The key where data is stored or gotten from
     * @param  mixed $value If value other than null, it is stored in the key
     *
     * @return mixed Returns either the data stored in key or the current object
     */
    private function getSet(string $key, object|array|string|int|float|bool|null $value = null): mixed {
        if ($value === null) {
            return $this->data[$key] ?? '';
        }
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Will manage the content so that it is a string when stored into data
     *
     * @access public
     * @param  mixed $data Url to file or a text string, if null returns null
     *
     * @return mixed Returns content as a string or null if no data
     */
    public function contentify(?string $data): string|null {
        return $data && is_file($data) ? file_get_contents($data) : $data;
    }

    /**
     * Will manage the input so that if it is json it converted to an array, otherwise input is returned
     *
     * @access public
     * @param  mixed $data If valid json, return array
     *
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
     * Stores the result of the variable to value change in template for each run
     *
     * @access private
     * @param  mixed $data String if the aim is to store the result, null if it is to get the stored result
     *
     * @return mixed Returns result as a string or this if result is set
     */
    private function result(?string $data = null): string|PHPlater {
        return $this->getSet('result', $data);
    }

    /**
     * Set or get tag by a constant
     *
     * @access public
     * @param  string $tag_constant The constant to set or get tag with
     * @param  string $tag The tag string, if you want to set the tag
     *
     * @return mixed The current PHPlater object if a set, the string tag if it is get
     */
    public function tag(int $tag_constant, string|null $tag = null): string|PHPlater {
        if ($tag === null) {
            return $this->data['tags'][$tag_constant] ?? '';
        }
        $this->data['tags'][$tag_constant] = $tag;
        return $this;
    }

    /**
     * Set all tags you want in one method or get all tags that are set
     *
     * @access public
     * @param  string $tags an array with constant as key, and tag as value
     *
     * @return mixed The current PHPlater object or an array with all the tags
     */
    public function tags(null|array $tags = null): array|PHPlater {
        if ($tags === null) {
            return $this->data['tags'];
        }
        foreach ($tags as $const => $tag) {
            $this->tag($const, $tag);
        }
        return $this;
    }

    /**
     * Set both template variable tags in one method
     *
     * Change tags if the current(default {{}}) tags are part of template
     *
     * @access public
     * @param  string $before Tag before variable in template
     * @param  string $after Tag after variable in template
     *
     * @return this The current PHPlater object
     */
    public function tagsVariables(string $before, string $after): PHPlater {
        return $this->tags([
            self::TAG_BEFORE => preg_quote($before),
            self::TAG_AFTER => preg_quote($after)
        ]);
    }

    /**
     * Set both list tags in one method
     *
     * Change tags if the current(default [[]]) tags are part of template
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  string $before Tag before list template in template
     * @param  string $after Tag after list template in template
     *
     * @return this The current PHPlater object
     */
    public function tagsList(string $before, string $after): PHPlater {
        return $this->tags([
            self::TAG_LIST_BEFORE => preg_quote($before),
            self::TAG_LIST_AFTER => preg_quote($after)
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
     *
     * @return this The current PHPlater object
     */
    public function tagsConditionals(string $before, string $after): PHPlater {
        return $this->tags([
            self::TAG_CONDITIONAL_BEFORE => preg_quote($before),
            self::TAG_CONDITIONAL_AFTER => preg_quote($after)
        ]);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function tagConditionalBefore(?string $tag = null): string|PHPlater {
        return $this->tag(self::TAG_CONDITIONAL_BEFORE, $tag ? preg_quote($tag) : $tag);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function tagConditionalAfter(?string $tag = null): string|PHPlater {
        return $this->tag(self::TAG_CONDITIONAL_AFTER, $tag ? preg_quote($tag) : $tag);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function tagListBefore(?string $tag = null): string|PHPlater {
        return $this->tag(self::TAG_LIST_BEFORE, $tag ? preg_quote($tag) : $tag);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function tagListAfter(?string $tag = null): string|PHPlater {
        return $this->tag(self::TAG_LIST_AFTER, $tag ? preg_quote($tag) : $tag);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function tagBefore(?string $tag = null): string|PHPlater {
        return $this->tag(self::TAG_BEFORE, $tag);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function tagAfter(?string $tag = null): string|PHPlater {
        return $this->tag(self::TAG_AFTER, $tag);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function tagKey(?string $tag = null): string|PHPlater {
        return $this->tag(self::TAG_LIST_KEY, $tag);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function conditionalSeparators(string $if, string $else): PHPlater {
        return $this->tag(self::TAG_IF, $if)->tag(self::TAG_ELSE, $else);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function pregDelimiter(?string $delimiter = null): string|PHPlater {
        return $this->tag(self::TAG_DELIMITER, $delimiter);
    }

    /**
     * @deprecated Deprecated since version v0.2.0 due to better naming, will be removed in v1.0.0
     */
    public function delimiter(?string $delimiter = null): string|PHPlater {
        return $this->pregDelimiter($delimiter);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function filterSeperator(?string $seperator = null): string|PHPlater {
        return $this->tag(self::TAG_FILTER, $seperator);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function chainSeperator(?string $seperator = null): string|PHPlater {
        return $this->tag(self::TAG_CHAIN, $seperator);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function argumentSeperator(?string $seperator = null): string|PHPlater {
        return $this->tag(self::TAG_ARGUMENT, $seperator);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function argumentListSeperator(?string $seperator = null): string|PHPlater {
        return $this->tag(self::TAG_ARGUMENT_LIST, $seperator);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function ifSeperator(?string $seperator = null): string|PHPlater {
        return $this->tag(self::TAG_IF, $seperator);
    }

    /**
     * @deprecated Deprecated since version v0.7.0 due to change in how accessed, will be removed in v1.0.0
     */
    public function elseSeperator(?string $seperator = null): string|PHPlater {
        return $this->tag(self::TAG_ELSE, $seperator);
    }

    /**
     * If the template is to be iterated over a collection of plates, then this method has to be called with true
     *
     * @access public
     * @param  $many true or false(default) according to whether or not there are many plates to iterate over
     *
     * @return mixed Either bool value, or the current PHPlater object
     */
    public function many(?bool $many = null): bool|PHPlater {
        return $this->getSet('many', $many);
    }

    /**
     * Set or get all plates at once
     *
     * The plates array is a key value store from which it is accessed from within the template
     *
     * @access public
     * @param  mixed $plates Either array with plates, json as string, or null to get all plates
     *
     * @return mixed The array of all the plates or the current object or current PHPlater if set
     */
    public function plates(null|string|array $plates = null): array|PHPlater {
        return $this->getSet('plates', $this->ifJsonToArray($plates));
    }

    /**
     * Set the template to act upon
     *
     * @access public
     * @param  mixed $data Url to file, a text string to set template, or null to return template
     *
     * @return mixed Current template as string, or the current object if data is set
     */
    public function content(?string $data = null): string|PHPlater {
        return $this->getSet('content', $this->contentify($data));
    }

    /**
     * Set or get the a plate (template variable)
     *
     * A plate is the value stored at the name position and is accessed from within the template
     *
     * @access public
     * @param  string $name The key position for where the plate is stored
     * @param  mixed $plate Object, array or string to store in the key position, or null to get the data in the key position
     *
     * @return mixed Plate asked for if it is a get operation, or the current object if data is set
     */
    public function plate(string $name, object|array|string|int|float|bool|null $plate = null): mixed {
        if ($plate === null) {
            $tag_before = stripslashes($this->tag(self::TAG_BEFORE));
            $tag_after = stripslashes($this->tag(self::TAG_AFTER));
            return $this->data['plates'][$name] ?? $tag_before . $name . $tag_after;
        }
        $this->data['plates'][$name] = $this->ifJsonToArray($plate);
        return $this;
    }

    /**
     * Run to render the template
     *
     * Replaces the template variables in the template, distinguished by tags, with the values from the plates
     *
     * @access public
     * @param  mixed $template Optional. The template to act upon if it is not set earlier.
     * @param  int $iterations To allow for nested plates, or variables that return variables, you can choose the amount of iterations that are to be done to the template
     *
     * @return string The finished result after all plates are applied to the template
     */
    public function render(?string $template = null, int $iterations = 1): string {
        $this->content($template);
        $this->content($this->renderList());
        $this->content($this->renderConditional());
        $tag_before = stripslashes($this->tag(self::TAG_BEFORE));
        $tag_after = stripslashes($this->tag(self::TAG_AFTER));
        $content = $this->many() ? $tag_before . ' 0 ' . $tag_after : $this->content();
        $this->result(preg_replace_callback($this->pattern(), [$this, 'find'], $content));
        if ($iterations-- && strstr($this->result(), $tag_before) && strstr($this->result(), $tag_after)) {
            return $this->render($this->result(), $iterations);
        }
        return $this->result();
    }

    /**
     * Get the pattern used to fetch all the tags in the template
     *
     * @access private
     *
     * @return string The pattern for preg_replace_callback
     */
    private function pattern(): string {
        $tags = preg_quote($this->tag(self::TAG_FILTER) . $this->tag(self::TAG_ARGUMENT) . $this->tag(self::TAG_CHAIN));
        $tag_before = $this->tag(self::TAG_BEFORE);
        $tag_after = $this->tag(self::TAG_AFTER);
        $delimiter = $this->tag(self::TAG_DELIMITER);
        return $delimiter . $tag_before . '\s*(?P<x>[\w,\-' . $tags . ']+?)\s*' . $tag_after . $delimiter;
    }

    /**
     * Render the lists in template if they are there
     *
     * Replaces the list tags in the template, for each value in the closest common array
     *
     * @access private
     *
     * @return string The finished result after all plates are applied to the template
     */
    private function renderList(): string {
        $tag_before = $this->tag(self::TAG_LIST_BEFORE);
        $tag_after = $this->tag(self::TAG_LIST_AFTER);
        $delimiter = $this->tag(self::TAG_DELIMITER);
        $pattern = $delimiter . $tag_before . '(?P<x>.+\.\..+?)' . $tag_after . $delimiter;
        return preg_replace_callback($pattern, [$this, 'findList'], $this->content());
    }

    /**
     * Render the conditional in template if they are there
     *
     * Replaces the conditional tags in the template, for each value in the closest common array
     *
     * @access private
     *
     * @return string The finished result after all plates are applied to the template
     */
    private function renderConditional(): string {
        $tag_before = $this->tag(self::TAG_CONDITIONAL_BEFORE);
        $tag_after = $this->tag(self::TAG_CONDITIONAL_AFTER);
        $delimiter = $this->tag(self::TAG_DELIMITER);
        $pattern = $delimiter . $tag_before . '(?P<x>.+?)' . $tag_after . $delimiter;
        return preg_replace_callback($pattern, [$this, 'findConditional'], $this->content());
    }

    /**
     * Finds the list variable and exchanges the position with the keys, and then renders the result
     *
     * @access private
     * @param  array $match The matched regular expression from renderList
     *
     * @return string The result after rendering all elements in the list
     */
    private function findList(array $match): string {
        preg_match_all($this->pattern(), $match['x'], $matches);
        $tag_chain = $this->tag(self::TAG_CHAIN);
        $delimiter = $this->tag(self::TAG_DELIMITER);
        $tag_before = $this->tag(self::TAG_BEFORE);
        $tag_after = $this->tag(self::TAG_AFTER);
        $tag_list_key = preg_quote($this->tag(self::TAG_LIST_KEY));
        $list_place = $tag_chain . $tag_chain;
        $all_before_parts = explode($list_place, $matches['x'][0]);
        $list_is_last = end($all_before_parts) == '';
        $list_is_first = reset($all_before_parts) == '';
        $core_parts = explode($tag_chain, $all_before_parts[0]);
        $list = $this->getList($this->plates(), $core_parts);
        $elements = [];
        $phplater = (new PHPlater())->plates($this->plates());
        foreach ($list as $key => $item) {
            $replace_with = ($list_is_first ? '' : $tag_chain) . $key . ($list_is_last ? '' : $tag_chain);
            $new_template = str_replace($list_place, $replace_with, $match['x']);
            $key_pattern = $delimiter . $tag_before . '\s*' . $tag_list_key . '\s*' . $tag_after . $delimiter;
            if (preg_match_all($key_pattern, $new_template, $key_matches) > 0) {
                foreach (array_unique($key_matches[0]) as $key_match) {
                    $new_template = str_replace($key_match, $key, $new_template);
                }
            }
            $elements[] = $phplater->render($new_template);
        }
        return implode('', $elements);
    }

    /**
     * Finds the conditionals and exchanges the position with the rendering and subsequent evaluation of values and then renders the result
     *
     * @access private
     * @param  array $match The matched regular expression from renderConditional
     *
     * @return string The result after rendering all conditionals
     */
    private function findConditional(array $match): string {
        $phplater = (new PHPlater())->plates($this->plates());
        $splitted_conditional = explode($this->tag(self::TAG_IF), $match['x']);
        $condition = trim($splitted_conditional[0]);
        $operators = ['\={2,3}', '\!\={1,2}', '\>\=', '\<\=', '\<\>', '\<\=\>', '\>', '\<', '%', '&{2}', '\|{2}', 'xor', 'and', 'or'];
        preg_match('/.+\s(' . implode('|', $operators) . ')\s.+/', $condition, $matches);
        $rendered_condition = false;
        if (isset($matches[1]) && $matches[1]) {
            $a_and_b = explode($matches[1], $condition);
            $a = trim($phplater->render($a_and_b[0]));
            $b = trim($phplater->render($a_and_b[1]));
            $rendered_condition = $this->evaluateOperation($a, $matches[1], $b);
        } else {
            $rendered_condition = $phplater->render($condition);
        }
        $splitted_if_else = explode($this->tag(self::TAG_ELSE), $splitted_conditional[1]);
        $ifTrue = trim($splitted_if_else[0] ?? '');
        $ifFalse = trim($splitted_if_else[1] ?? '');
        if ($rendered_condition) {
            return $phplater->render($ifTrue);
        }
        return $phplater->render($ifFalse);
    }

    /**
     * Method to return value of operation when done with a matched string operand
     *
     * @access private
     * @param  string $a The first value
     * @param  string $operator The operand to evaluate first and second value with
     * @param  string $b The second value
     *
     * @return bool|int The result after evaluating the values with the given operand
     */
    private function evaluateOperation(string $a, string $operator, string $b): bool|int {
        $a = is_numeric($a) ? (int) $a : $a;
        $b = is_numeric($b) ? (int) $b : $b;

        return match ($operator) {
            '==' => $a == $b,
            '!==' => $a !== $b,
            '===' => $a === $b,
            '!=' => $a != $b,
            '>=' => $a >= $b,
            '<=' => $a <= $b,
            '>' => $a > $b,
            '<' => $a < $b,
            '<>' => $a <> $b,
            '<=>' => $a <=> $b,
            '%' => $a % $b,
            '&&', 'and' => $a && $b,
            '||', 'or' => $a || $b,
            'xor' => $a xor $b,
            default => $a
        };
    }

    /**
     * Finds and returns the list from the location ['one', 'two', 'three'] in plates
     *
     * @access private
     * @param array $plates The plates array
     * @param type $array List of values to iterate in plates
     *
     * @return array
     */
    private function getList(array $plates, array $array = []): string|array {
        $key = array_shift($array);
        if ($array) {
            return $this->getList($plates[$key], $array);
        }
        return $key == '' ? $plates : $plates[$key];
    }

    /**
     * Begins with the root plated and iterates through every one and extracts the matching value
     *
     * Each variable can be nested and in the end, the last vaulue is returned as the exhange for the match
     * Example: key.value_as_array.value_as_object.method_to_call
     *
     * @access private
     * @param  array $match The matched regular expression from render
     *
     * @return string The result after exchanging all the matched plates
     */
    private function find(array $match): string {
        if ($this->many()) {
            $all_plates = '';
            foreach ($this->plates() as $plates) {
                $all_plates .= (new PHPlater())->plates($plates)->render($this->content());
            }
            return $all_plates;
        }
        [$parts, $filters] = $this->getFiltersAndParts($match['x']);
        $plate = $this->plate(array_shift($parts));
        foreach ($parts as $part) {
            $plate = $this->extract($this->ifJsonToArray($plate), $part);
        }
        return $this->callFilters($plate, $filters);
    }

    /**
     * Adds and gets the filter function, as well as calls it
     * Note that if $value is a string of a callable function it will be considered a set of the function
     * Otherwise the filter function is called with $value as argument
     *
     * @access public
     * @param  mixed $filter The name of the filter, either when set or when called
     * @param  string $value The callable function, or the argument to call function with
     *
     * @return mixed The result of the called function, the function itself, or the current PHPlater object
     */
    public function filter(string $filter, callable|string|null|array $value = null): int|string|callable|PHPlater {
        if ($filter && is_callable($value)) {
            return $this->getSet($filter, $value);
        } else if ($value === null) {
            return $this->getSet($filter);
        } else if ($filter) {
            [$filter_function, $filter_arguments] = $this->getFunctionAndArguments($filter, $value);
            if ($filter_arguments) {
                array_unshift($filter_arguments, $value);
                return call_user_func_array($filter_function, $filter_arguments);
            }
            return $filter_function($value);
        }
        return $this;
    }

    /**
     * Helper method to separate nesting and filters
     *
     * @access private
     * @param  string $plate The plate string
     *
     * @return array Nesting parts and filters separated into array
     */
    private function getFiltersAndParts(string $plate): array {
        $parts = explode($this->tag(self::TAG_FILTER), $plate);
        $first_part = array_shift($parts);
        return [explode($this->tag(self::TAG_CHAIN), $first_part), $parts];
    }

    /**
     * Helper method to separate filter and arguments
     *
     * @access private
     * @param  string $plate The filter string
     *
     * @return array Filter as first, arguments in second
     */
    private function getFunctionAndArguments(string $filter): array {
        $parts = explode($this->tag(self::TAG_ARGUMENT), $filter);
        return [$this->filter($parts[0]), isset($parts[1]) ? explode($this->tag(self::TAG_ARGUMENT_LIST), $parts[1]) : []];
    }

    /**
     * Will manage the content so that it is a string when stored into data
     *
     * @access private
     * @param  mixed $plate The last plate to check action on
     * @param  string $part The key of the plate to extract value from
     *
     * @return mixed The content of the plate, to be acted upon on the next variable depth
     */
    private function extract(object|array|string|int|float|bool|null $plate, string $part): mixed {
        $return = '';
        if (is_object($plate)) {
            if (method_exists($plate, $part)) {
                $return = call_user_func([$plate, $part]);
            } else if (is_a($plate, get_class($this))) {
                $return = $plate->plate($part);
            } else if (property_exists($plate, $part) && isset($plate->$part)) {
                $return = $plate->$part;
            }
        } else if (isset($plate[$part])) {
            $return = $plate[$part];
        }
        return $return;
    }

    /**
     * Checks if there are filters on the plate, and applies them
     *
     * @access private
     * @param mixed $plate The plate to check
     * @return mixed The plate, or if filters applied then the resulting string
     */
    private function callFilters(object|array|string|int|float|bool|null $plate, array $filters = []): mixed {
        foreach ($filters as $filter) {
            $plate = $this->filter($filter, $plate);
        }
        return $plate;
    }

}
