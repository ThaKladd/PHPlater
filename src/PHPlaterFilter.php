<?php

/**
 * The PHPlaterFilter class
 *
 * This class manages the filters within PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */

class PHPlaterFilter extends PHPlaterBase {

     /**
     * Adds the callable filter function to class
     *
     * @access public
     * @param string $filter The name of the filter
     * @param callable $function The callable function
     * @return void
     */
    public function setFilter(string $filter, callable $function): void {
        $this->filters[$filter] = $function;
    }

    /**
     * Get the callable filter function from class
     *
     * @access public
     * @param string $filter The name of the filter
     * @return callable The function
     */
    public function getFilter(string $filter): callable {
        return $this->filters[$filter];
    }

    /**
     * Calls the filter function
     *
     * @access public
     * @param string $filter The name of the filter
     * @param string|array $arguments The argument to call function with
     * @return mixed The result of the called function
     */
    public function doFilter(string $filter, string|array $arguments): mixed {
        [$filter_function, $filter_arguments] = $this->getFunctionAndArguments($filter);
        if ($filter_arguments) {
            array_unshift($filter_arguments, $arguments);
            return call_user_func_array($filter_function, $filter_arguments);
        }
        return $filter_function($arguments);
    }

    /**
     * Helper method to separate filter and arguments
     *
     * @access private
     * @param  string $filter The filter string
     * @return array Filter as first, arguments in second
     */
    private function getFunctionAndArguments(string $filter): array {
        $parts = explode(self::getTag(self::TAG_ARGUMENT), $filter);
        return [$this->getFilter($parts[0]), isset($parts[1]) ? explode(self::getTag(self::TAG_ARGUMENT_LIST), $parts[1]) : []];
    }

    /**
     * Checks if there are filters on the plate, and applies them
     *
     * @access public
     * @param mixed $plate The plate to check
     * @param array $filters List of filters
     * @return mixed The plate, or if filters applied then the resulting string
     */
    public function callFilters(mixed $plate, array $filters = []): mixed {
        foreach ($filters as $filter) {
            $plate = $this->doFilter($filter, $plate);
        }
        return $plate;
    }
}