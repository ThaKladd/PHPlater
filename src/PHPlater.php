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
     *
     * @var array $data {\
     *  @type string $content Content of the template, either from string or file\
     *  @type string $result Where the result of the render is stored\
     *  @type array $plates Array of key value pairs that is the structure for the variables in the template. Can be multidimensional\
     *  @type string $delimiter Regex delimiter if the default is in use inside template\
     *  @type string $tag_before Tag before variable in template\
     *  @type string $tag_after Tag after variable in template.\
     * }
     */
    private array $data = [
        'content' => '',
        'result' => '',
        'plates' => [],
        'preg_delimiter' => '|',
        'filter_seperator' => '|',
        'tag_before' => '{{',
        'tag_after' => '}}'
    ];

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
        return $this->getSet('tag_before', $tag ? preg_quote($tag) : $tag);
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
        return $this->getSet('tag_after', $tag ? preg_quote($tag) : $tag);
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
        $pattern = $this->pregDelimiter() . $this->tagBefore() . '\s*(?P<x>[a-zA-Z0-9_\-\.\\' . $this->filterSeperator() . ']+?)\s*' . $this->tagAfter() . $this->pregDelimiter();
        $this->result(preg_replace_callback($pattern, [$this, 'find'], $this->content()));
        if ($iterations-- && strstr($this->result(), $this->tagBefore()) && strstr($this->result(), $this->tagAfter())) {
            return $this->render($this->result(), $iterations);
        }
        return $this->result();
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
            return $this->filter($filter)($value);
        }
        return $this;
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
        [$parts, $filters] = $this->getFiltersAndParts($match['x']);
        $plate = $this->plate(array_shift($parts));
        foreach ($parts as $part) {
            $plate = $this->extract($this->ifJsonToArray($plate), $part);
        }
        return $this->callFilters($plate, $filters);
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
        return [explode('.', $first_part), $parts];
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
