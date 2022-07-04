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
        'plates' => []
    ];

    public function __construct() {
        $this->tags('{{', '}}');
        $this->tagsList('[[', ']]');
        $this->tagsConditional('((', '))');
        $this->conditionalSeparators('??', '::');
        $this->argumentSeperator(':');
        $this->argumentListSeperator(',');
        $this->chainSeperator('.');
        $this->filterSeperator('|');
        $this->pregDelimiter('|');
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
     * Set both template tags in one method
     *
     * Change tags if the current(default {{}}) tags are part of template
     *
     * @access public
     * @param  string $before Tag before variable in template
     * @param  string $after Tag after variable in template
     *
     * @return this The current PHPlater object
     */
    public function tags(string $before, string $after): PHPlater {
        return $this->tagBefore($before)->tagAfter($after);
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
        return $this->tagListBefore($before)->tagListAfter($after);
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
    public function tagsConditional(string $before, string $after): PHPlater {
        return $this->tagConditionalBefore($before)->tagConditionalAfter($after);
    }

    /**
     * Set or get start conditional tag
     *
     * Change tag if the current(default (() tag is part of template.
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  $tag If set, this will be the new start tag of the conditional template in template
     *
     * @return mixed Either the content of the tag, or the current PHPlater object
     */
    public function tagConditionalBefore(?string $tag = null): string|PHPlater {
        return $this->getSet('tag_conditional_before', $tag ? preg_quote($tag) : $tag);
    }

    /**
     * Set or get end conditional tag
     *
     * Change tag if the current(default ))) tag is part of template.
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  $tag If set, this will be the new end tag of the conditional template in template
     *
     * @return mixed Either the content of the tag, or the current PHPlater object
     */
    public function tagConditionalAfter(?string $tag = null): string|PHPlater {
        return $this->getSet('tag_conditional_after', $tag ? preg_quote($tag) : $tag);
    }

    /**
     * Set or get start list tag
     *
     * Change tag if the current(default [[) tag is part of template.
     * For instance, if you want to change it to how other engines do, you can change it to  {{#
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  $tag If set, this will be the new start tag of the list template in template
     *
     * @return mixed Either the content of the tag, or the current PHPlater object
     */
    public function tagListBefore(?string $tag = null): string|PHPlater {
        return $this->getSet('tag_list_before', $tag ? preg_quote($tag) : $tag);
    }

    /**
     * Set or get end list tag
     *
     * Change tag if the current(default ]]) tag is part of template.
     * For instance, you change this to #}}
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  $tag If set, this will be the new end tag of the list template in template
     *
     * @return mixed Either the content of the tag, or the current PHPlater object
     */
    public function tagListAfter(?string $tag = null): string|PHPlater {
        return $this->getSet('tag_list_after', $tag ? preg_quote($tag) : $tag);
    }

    /**
     * Set or get start template tag
     *
     * Change tag if the current(default {{) tag is part of template.
     * For instance, if you want to use HTML comment as the tag, you change this to <!--
     *
     * @access public
     * @param  $tag If set, this will be the new start tag of the variable in template
     *
     * @return mixed Either the content of the tag, or the current PHPlater object
     */
    public function tagBefore(?string $tag = null): string|PHPlater {
        return $this->getSet('tag_before', $tag);
    }

    /**
     * Set or get end template tag
     *
     * Change tag if the current(default }}) tag is part of template.
     * For instance, if you want to use HTML comment as the tag, you change this to -->
     *
     * @access public
     * @param  $tag If set, this will be the new end tag of the variable in template
     *
     * @return mixed Either the content of the tag, or the current PHPlater object
     */
    public function tagAfter(?string $tag = null): string|PHPlater {
        return $this->getSet('tag_after', $tag);
    }

    /**
     * Set both if and else tags in one method
     *
     * Change tags if the current(default ?? and ::) tags are part of template
     *
     * @access public
     * @param  string $if Tag for if
     * @param  string $else Tag for else
     *
     * @return this The current PHPlater object
     */
    public function conditionalSeparators(string $if, string $else): PHPlater {
        return $this->ifSeperator($if)->elseSeperator($else);
    }

    /**
     * Set or get preg(regex) delimiter
     *
     * Change delimiter if the current delimiter is part of template
     *
     * @access public
     * @param  string $delimiter The delimiter to use for preg method
     *
     * @return mixed Either the delimiter string, or the current PHPlater object
     */
    public function pregDelimiter(?string $delimiter = null): string|PHPlater {
        return $this->getSet('preg_delimiter', $delimiter);
    }

    /**
     * @deprecated Deprecated since version v0.2.0 due to better naming, will be removed in v1.0.0
     */
    public function delimiter(?string $delimiter = null): string|PHPlater {
        return $this->pregDelimiter($delimiter);
    }

    /**
     * Set or get filter separator
     *
     * Change delimiter if the current delimiter is part of template
     *
     * @access public
     * @param  string $separator The separator to use to separate the filter function
     *
     * @return mixed Either the separator string, or the current PHPlater object
     */
    public function filterSeperator(?string $seperator = null): string|PHPlater {
        return $this->getSet('filter_seperator', $seperator);
    }

    /**
     * Set or get chain separator
     *
     * From the standard array.obj.method, this can be changed to array->obj->method
     *
     * @access public
     * @param  string $separator The separator to use to separate parts of the chain
     *
     * @return mixed Either the separator string, or the current PHPlater object
     */
    public function chainSeperator(?string $seperator = null): string|PHPlater {
        return $this->getSet('chain_seperator', $seperator);
    }

    /**
     * Set or get argument separator
     * Can be confused with argumentListSeperator. This method is to delimit where method name ends and arguments begins
     * Default value is :
     *
     * Change delimiter if the current delimiter is part of template
     *
     * @access public
     * @param  string $separator The separator to use to separate the filter function from the arguments
     *
     * @return mixed Either the separator string, or the current PHPlater object
     */
    public function argumentSeperator(?string $seperator = null): string|PHPlater {
        return $this->getSet('argument_seperator', $seperator);
    }

    /**
     * Set or get argument list separator
     * Can be confused with argumentSeperator. This method is the seperator between the arguments if there are more than one.
     * The default value is ,
     *
     * Change delimiter if the current delimiter is part of template
     *
     * @access public
     * @param  string $separator The separator to use to separate the filter function from the arguments
     *
     * @return mixed Either the separator string, or the current PHPlater object
     */
    public function argumentListSeperator(?string $seperator = null): string|PHPlater {
        return $this->getSet('argument_list_seperator', $seperator);
    }

    /**
     * Set or get if separator
     * Default is ??
     *
     * Change delimiter if the current delimiter is part of template
     *
     * @access public
     * @param  string $separator The separator to use to separate the filter function
     *
     * @return mixed Either the separator string, or the current PHPlater object
     */
    public function ifSeperator(?string $seperator = null): string|PHPlater {
        return $this->getSet('if_seperator', $seperator);
    }

    /**
     * Set or get else separator
     * Default is ::
     *
     * Change delimiter if the current delimiter is part of template
     *
     * @access public
     * @param  string $separator The separator to use to separate the filter function
     *
     * @return mixed Either the separator string, or the current PHPlater object
     */
    public function elseSeperator(?string $seperator = null): string|PHPlater {
        return $this->getSet('else_seperator', $seperator);
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
            return $this->data['plates'][$name] ?? $this->tagBefore() . $name . $this->tagAfter();
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
        $content = $this->many() ? $this->tagBefore() . ' 0 ' . $this->tagAfter() : $this->content();
        $this->result(preg_replace_callback($this->pattern(), [$this, 'find'], $content));
        if ($iterations-- && strstr($this->result(), $this->tagBefore()) && strstr($this->result(), $this->tagAfter())) {
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
        $tags = preg_quote($this->filterSeperator() . $this->argumentSeperator() . $this->chainSeperator());
        $before_tag = preg_quote($this->tagBefore());
        $after_tag = preg_quote($this->tagAfter());
        return $this->pregDelimiter() . $before_tag . '\s*(?P<x>[\w,\-' . $tags . ']+?)\s*' . $after_tag . $this->pregDelimiter();
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
        $pattern = $this->pregDelimiter() . $this->tagListBefore() . '(?P<x>.+\.\..+?)' . $this->tagListAfter() . $this->pregDelimiter();
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
        $pattern = $this->pregDelimiter() . $this->tagConditionalBefore() . '(?P<x>.+?)' . $this->tagConditionalAfter() . $this->pregDelimiter();
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
        $list_place = $this->chainSeperator() . $this->chainSeperator();
        $all_before_parts = explode($list_place, $matches['x'][0]);
        $list_is_last = end($all_before_parts) == '';
        $list_is_first = reset($all_before_parts) == '';
        $core_parts = explode($this->chainSeperator(), $all_before_parts[0]);
        $list = $this->getList($this->plates(), $core_parts);
        $elements = [];
        $phplater = (new PHPlater())->plates($this->plates());
        foreach ($list as $key => $item) {
            $replace_with = ($list_is_first ? '' : $this->chainSeperator()) . $key . ($list_is_last ? '' : $this->chainSeperator());
            $new_template = str_replace($list_place, $replace_with, $match['x']);
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
        $splitted_conditional = explode($this->ifSeperator(), $match['x']);
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
        $splitted_if_else = explode($this->elseSeperator(), $splitted_conditional[1]);
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
        $parts = explode($this->filterSeperator(), $plate);
        $first_part = array_shift($parts);
        return [explode($this->chainSeperator(), $first_part), $parts];
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
        $parts = explode($this->argumentSeperator(), $filter);
        return [$this->filter($parts[0]), isset($parts[1]) ? explode($this->argumentListSeperator(), $parts[1]) : []];
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
