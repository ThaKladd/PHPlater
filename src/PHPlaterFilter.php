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
     * @param string|array<string> $arguments The argument to call function with
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
     * @return array<mixed> Filter as first, arguments in second
     */
    private function getFunctionAndArguments(string $filter): array {
        $parts = explode(Tag::ARGUMENT->get(true), $filter);
        $callable = null;
        if (method_exists($this, $parts[0])) {
            $callable = [$this, $parts[0]];
        } else {
            $callable = $this->getFilter($parts[0]);
        }
        $arguments = isset($parts[1]) ? explode(Tag::ARGUMENT_LIST->get(true), $parts[1]) : [];
        return [$callable, $arguments];
    }

    /**
     * Checks if there are filters on the plate, and applies them
     *
     * @access public
     * @param mixed $plate The plate to check
     * @param array<string> $filters List of filters
     * @return mixed The plate, or if filters applied then the resulting string
     */
    public function callFilters(mixed $plate, array $filters = []): mixed {
        foreach ($filters as $filter) {
            $plate = $this->doFilter($filter, $plate);
        }
        return $plate;
    }

    //End of the object -> all the predefined filters. Maybe group them better later.

    /**
     * Here for the testing, not sure it will be shipped
     *
     * @param string $plate
     * @param string|int $length
     * @return string
     */
    public static function lipsum(string $plate, string|int $length = 100): string {
        $length = (int) $length;
        $lipsum = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
        if ($length && strlen($lipsum) >= $length) {
            return substr($lipsum, 0, $length);
        }
        return $lipsum;
    }

    /**
     * Here for the testing, not sure it will be shipped
     *
     * @param string $plate
     * @param string $min
     * @param string $max
     * @return int
     */
    public static function rand(string $plate, string $min, string $max): int {
        return rand((int) $min, (int) $max);
    }

    /**
     * Here for the testing, not sure it will be shipped
     *
     * @param string $plate
     * @param string $min
     * @param string $max
     * @return array
     */
    public static function range(string $plate, string $min, string $max): array {
        return range((int) $min, (int) $max);
    }

}
