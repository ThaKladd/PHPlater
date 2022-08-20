<?php

/**
 * The PHPlaterTag class
 *
 * This class manages the tags within PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */
use Error\RuleBrokenError;

class PHPlaterFilter extends PHPlaterBase {

    /**
     * Creates PHPlaterFilter object and initializes it
     *
     * @access public
     */
    public function __construct(PHPLater $phplater) {
        $this->core($phplater);
    }

     /**
     * Adds and gets the filter function, as well as calls it
     * Note that if $value is a string of a callable function it will be considered a set of the function
     * Otherwise the filter function is called with $value as argument
     *
     * @access public
     * @param  mixed $filter The name of the filter, either when set or when called
     * @param  string $value The callable function, or the argument to call function with
     * @return mixed The result of the called function, the function itself, or the current object
     */
    public function filter(string $filter, callable|string|null|array $value = null): int|string|callable|object {
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
     * Helper method to separate filter and arguments
     *
     * @access private
     * @param  string $plate The filter string
     * @return array Filter as first, arguments in second
     */
    private function getFunctionAndArguments(string $filter): array {
        $parts = explode(PHPlaterTag::tag(PHPlaterTag::TAG_ARGUMENT), $filter);
        return [$this->filter($parts[0]), isset($parts[1]) ? explode(PHPlaterTag::tag(PHPlaterTag::TAG_ARGUMENT_LIST), $parts[1]) : []];
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