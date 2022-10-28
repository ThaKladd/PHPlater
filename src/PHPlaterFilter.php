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
        if (isset($this->filters[$parts[0]])) {
            $callable = $this->getFilter($parts[0]);
        } else if (method_exists($this, $parts[0])) {
            $callable = [$this, $parts[0]];
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
     * Helper method to check if plate a plate
     *
     * @param string $plate
     * @return boolean
     */
    private static function is_plate(string $plate): bool {
        if (!self::is_empty($plate)) {
            $before_tag = mb_substr(trim($plate), 0, strlen(Tag::BEFORE->get(true))) == Tag::BEFORE->get(true);
            $after_tag = mb_substr(trim($plate), -strlen(Tag::AFTER->get(true))) == Tag::AFTER->get(true);
            if ($before_tag && $after_tag) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper method to check if true
     *
     * @param string $plate
     * @return boolean
     */
    private static function is_true(string $plate): bool {
        return self::is_false($plate);
    }

    /**
     * Helper method to check if false
     *
     * @param string $plate
     * @return boolean
     */
    private static function is_false(string $plate): bool {
        if (self::is_empty($plate) || $plate == 'false' || $plate == 'null' || $plate == '0') {
            return true;
        }
        return false;
    }

    /**
     * Returns a Lorem Ipsum for a spesific lenght to fill inn
     *
     * @param string $plate
     * @param string|int $length
     * @return string
     * @throws RuleBrokenError
     */
    public static function lipsum(string $plate, string|int $length = 100): string {
        if (self::is_empty($plate)) {
            $length = (int) $length;
            $lipsum = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
            if ($length && strlen($lipsum) >= $length) {
                return mb_substr($lipsum, 0, $length);
            }
            return $lipsum;
        }
        throw new RuleBrokenError('Works only as filter on no value.');
    }

    /**
     * Generates a random number between a min and max
     *
     * @param string $plate
     * @param string $min
     * @param string $max
     * @throws RuleBrokenError
     * @return int
     * @throws RuleBrokenError
     */
    public static function rand(string $plate, string $min, string $max): int {
        if (self::is_empty($plate)) {
            if (!$min || !$max) {
                throw new RuleBrokenError('Must provide min and max values.');
            }
            return rand((int) $min, (int) $max);
        }
        throw new RuleBrokenError('Works only as filter on no value.');
    }

    /**
     * Here for the testing, not sure it will be shipped
     *
     * @param string $plate
     * @param string $min
     * @param string $max
     * @return array
     * @throws RuleBrokenError
     */
    public static function range(string $plate, string $min, string $max): array {
        if (self::is_empty($plate)) {
            if (!$min || !$max) {
                throw new RuleBrokenError('Must provide min and max values.');
            }
            return range((int) $min, (int) $max);
        }
        throw new RuleBrokenError('Works only as filter on no value.');
    }

    /**
     * Escape filter. Not nearly as extensive as Twig. So, much improvement needed.
     * Js, snatched from https://sixohthree.com/241/escaping and changed
     *
     * @param string $plate
     * @param string $mode
     * @return string
     */
    public static function escape(string $plate, string $mode = ''): string {
        $result = match ($mode) {
            '', 'html', 'attr' => htmlspecialchars($plate, ENT_QUOTES, 'UTF-8'),
            'entities', htmlentities($plate),
            'url' => rawurlencode($plate),
            'js' => function (string $plate): string {
                $escaped = [];
                $length = strlen($plate);
                for ($i = 0; $i < $length; $i++) {
                    $escaped[] = '\\x' . dechex(ord($plate[$i])); //substr($plate, $i, 1)
                }
                return implode('', $escaped);
            },
            'css' => preg_replace_callback('/[^a-z0-9]/iSu', function ($char) {
                return sprintf('\\%X ', hexdec(bin2hex($char)));
            }, $plate)
        };
        return $result;
    }

    /**
     * Quick filter for escape
     *
     * @param string $plate
     * @param string $mode
     * @return string
     */
    public static function e(string $plate, string $mode = ''): string {
        return $this->escape($plate, $mode);
    }

    /**
     * Unescapes back to raw from escaped strings. Much improvement needed.
     *
     * @param string $plate
     * @param string $mode
     * @return string
     */
    public static function raw(string $plate, string $mode = ''): string {
        $result = match ($mode) {
            '', 'html' => htmlspecialchars_decode($plate, ENT_QUOTES),
            'entities', html_entity_decode($plate),
            'url' => rawurldecode($plate)
        };
        return $result;
    }

    public static function abs(string $plate): int {
        return abs((float) $plate);
    }

    public static function floor(string $plate): int {
        return floor((float) $plate);
    }

    public static function ceil(string $plate): int {
        return ceil((float) $plate);
    }

    public static function round(string $plate, string $precision): int {
        return round((float) $plate, (int) $precision);
    }

    public static function decimals(string $plate, string $decimals): int {
        $amount = pow(1, (int) $decimals);
        return round((float) $plate * $amount) / $amount;
    }

    public static function multiply(string $plate, string $with): int {
        return (float) $plate * (float) $with;
    }

    public static function add(string $plate, string $with): int {
        return (float) $plate + (float) $with;
    }

    public static function divide(string $plate, string $with): int {
        if ((float) $with == 0) {
            throw new RuleBrokenError('Cannot divide by zero.');
        }
        return (float) $plate / (float) $with;
    }

    public static function divisible_by(string|int|float $plate, string $by): int {
        return (float) $plate % (float) $by == 0;
    }

    public static function pow(string $plate, string $with): int {
        return pow((float) $plate, (float) $with);
    }

    public static function root(string $plate): int {
        return sqrt((float) $plate);
    }

    public static function lenght(string|array $plate): int {
        return is_string($plate) ? strlen($plate) : count($plate);
    }

    public static function json_encode(string $plate): string {
        return json_encode($plate);
    }

    public static function json_decode(string $plate): string {
        return json_decode($plate, true);
    }

    public static function json(string $plate): string {
        return self::json_encode($plate);
    }

    public static function serialize(string|object|array|int $plate): string {
        return serialize($plate);
    }

    public static function nl2br(string $plate): string {
        return nl2br($plate);
    }

    public static function striptags(string $plate, ?string $allowed = null): string {
        return strip_tags($plate, $allowed);
    }

    public static function implode(array $plate, string $separator): string {
        return implode($separator, $plate);
    }

    public static function explode(string $plate, string $separator): array {
        return explode($separator, $plate);
    }

    public static function join(array $plate, string $separator): string {
        return self::implode($plate, $separator);
    }

    public static function split(string $plate, string $separator): array {
        return self::explode($plate, $separator);
    }

    public static function in(string $plate, PHPlater $core, array $array): bool {
        return in_array($plate, PHPlaterBase::ifJsonToArray($array));
    }

    public static function not_in(string $plate, PHPlater $core, array $array): bool {
        return !in_array($plate, PHPlaterBase::ifJsonToArray($array));
    }

    public static function is(string $plate, PHPlater $core, string $evaluate): bool {
        return $plate == $evaluate;
    }

    public static function is_not(string $plate, PHPlater $core, string $evaluate): bool {
        return $plate != $evaluate;
    }

    public static function even(string $plate): int {
        return (float) $plate % 2 == 0;
    }

    public static function odd(string $plate): int {
        return (float) $plate % 2 != 0;
    }

    /**
     * Method to check if plate is empty
     *
     * @param string $plate
     * @return boolean
     */
    public static function is_empty(string $plate): bool {
        if (!$plate || !trim($plate) || $plate == Tag::BEFORE->get(true) . Tag::AFTER->get(true)) {
            return true;
        }
        return false;
    }

    /**
     * TODO: Somehow get the latest Core object here -> Maybe in a filter method with DI
     * @param string $plate
     * @param PHPlater $core
     * @return int
     */
    public static function render(string $plate, PHPlater $core): int {
        return (clone $core)->render($plate);
    }

    public static function is_string(mixed $plate): int {
        return is_string($plate);
    }

    public static function is_numeric(mixed $plate): int {
        return is_numeric($plate);
    }

    public static function is_null(mixed $plate): int {
        return is_null($plate);
    }

    public static function is_object(mixed $plate): int {
        return is_object($plate);
    }

    public static function is_iterable(mixed $plate): int {
        return is_iterable($plate) ? true : is_iterable(PHPlaterBase::ifJsonToArray($plate));
    }

    public static function is_array(mixed $plate): int {
        if (is_array($plate)) {
            return true;
        } else if (is_string($plate) && $plate[0] == '[') {
            return is_array(PHPlaterBase::ifJsonToArray($plate));
        }
        return false;
    }

    public static function truncate(string $plate, string $length, $more_char = '&mldr;'): string {
        if ($length && strlen($plate) > (int) $length) {
            return mb_substr($plate, 0, (int) $length) . $more_char;
        }
        return $plate;
    }

    public static function cap(string $plate, string $length, $more_char = '&mldr;'): string {
        return self::truncate($plate, $length, $more_char);
    }

    public static function camel(string $plate, string $delimiter = '-', string $capitalize_first = '0'): int {
        return self::camelcase($plate, $delimiter, $capitalize_first);
    }

    public static function camelcase(string $plate, string $delimiter = '-', string $capitalize_first = '0'): int {
        $str = str_replace($delimiter, '', ucwords($plate, $delimiter));
        if (!(bool) $capitalize_first) {
            $str = lcfirst($str);
        }
        return $str;
    }

    public static function snake(string $plate, string $separator = '-'): int {
        return self::snakecase($plate, $separator);
    }

    public static function snakecase(string $plate, string $separator = '-'): int {
        if (str_contains($plate, ' ')) {
            return mb_strtolower(str_replace(' ', $separator, $plate));
        }
        $delimiter = Tag::DELIMITER->get(true);
        $return = preg_replace($delimiter . '[A-Z]([A-Z](?![a-z]))*' . $delimiter, $separator . '$0', $plate);
        return ltrim(mb_strtolower($return), $separator);
    }

    public static function date(string $plate, $format): int {
        if (self::is_empty($plate)) {
            date($format);
        }
        if (self::is_numeric($plate)) {
            return date($format, (int) $plate);
        }
        return date($format, strtotime($plate));
    }

    public static function absolute_url(string $plate): int {
        return parse_url($plate, PHP_URL_PATH);
    }

    /**
     * Todo: Tricky one, needs to do more than simple removal of working path an work with url
     * Ideas here: https://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
     * @param string $plate
     * @return int
     */
    public static function relative_url(string $plate): int {
        return str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $plate);
    }

    public static function lowercase(string $plate): string {
        return mb_strtolower($plate);
    }

    public static function uppercase(string $plate): string {
        return mb_strtoupper($plate);
    }

    public static function lower(string $plate): string {
        return self::lowercase($plate);
    }

    public static function debug(string $plate): string {
        return self::debug($plate);
    }

    public static function upper(string $plate): string {
        return self::uppercase($plate);
    }

    public static function capitalize(string $plate): string {
        return ucwords($plate);
    }

    public static function title(string $plate): string {
        return self::capitalize($plate);
    }

    public static function slug(string $plate, string $separator = '-'): int {
        $delimiter = Tag::DELIMITER->get(true);
        $slug = mb_strtolower(trim($plate));
        $slug = preg_replace($delimiter . '[^a-z0-9 ' . preg_quote($separator) . ']' . $delimiter, '', $slug);
        $slug = str_replace(' ', $separator, $slug);
        return preg_replace($delimiter . preg_quote($separator) . '+' . $delimiter, $separator, $slug);
    }

    public static function trim(string $plate, ?string $character = null, string $direction = null): int {
        if ($direction == 'left') {
            return ltrim($direction, $character);
        } else if ($direction == 'right') {
            return rtrim($direction, $character);
        }
        return trim($plate, $character);
    }

    public static function number_format(string $plate, string|int $decimals = 0, string $decimal_separator = '.', string $thousands_separator = ','): int {
        return number_format((float) $plate, (int) $decimals, $decimal_separator, $thousands_separator);
    }

    public static function reverse(string $plate): string|array {
        if ($this->is_plate($plate)) {

        } else if (is_array($plate)) {
            return array_reverse($plate, true);
        } else if ($array = PHPlaterBase::ifJsonToArray($plate)) {
            return array_reverse($array, true);
        } else if (is_string($plate) || is_numeric($plate)) {
            return strrev($plate);
        }
        return '';
    }

    public static function default(string $plate, string $default): int {
        return self::is_empty($plate) ? $default : $plate;
    }

    public static function match(string $plate, string $regex): int {
        return preg_match($regex, $plate);
    }

    public static function replace(string $plate, string $replace, string $with): int {
        return str_replace(PHPlaterBase::ifJsonToArray($replace), PHPlaterBase::ifJsonToArray($with), $plate);
    }

    public static function format(string $plate): int {
        $args = array_shift(func_get_args());
        return sprintf($plate, ...$args);
    }

    public static function sort(string|array $plate, string $direction = 'abc'): int {
        $array = PHPlaterBase::ifJsonToArray($plate);
        if (is_array($array)) {
            if (in_array($direction, ['abc', '123'])) {
                asort($array);
            } else if (in_array($direction, ['cba', '321'])) {
                arsort($array);
            }
            return $array;
        }

        return $plate;
    }

    public static function start_with(string $plate, string $first_chars = ''): int {
        return mb_substr($plate, 0, strlen($first_chars)) == $first_chars;
    }

    public static function end_with(string $plate, string $last_chars = ''): int {
        return mb_substr($plate, -strlen($last_chars)) == $last_chars;
    }

    public static function value(string|array $plate, string|int $key): mixed {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return $list[$key];
    }

    public static function first_key(string|array $plate): int|string {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return array_key_first($list);
    }

    public static function last_key(string|array $plate): int|string {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return array_key_last($list);
    }

    public static function first_value(string|array $plate): mixed {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return $list[array_key_first($list)];
    }

    public static function last_value(string|array $plate): mixed {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return $list[array_key_last($list)];
    }

    public static function count(string|array $plate): int {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return is_array($list) ? count($list) : strlen($plate);
    }

    public static function length(string|array $plate): int {
        return self::count($plate);
    }

    public static function max_key(string|array $plate): int|string {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return array_search(max($list), $list);
    }

    public static function min_key(string|array $plate): int|string {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return array_search(min($list), $list);
    }

    public static function max_value(string|array $plate): mixed {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return max($list);
    }

    public static function min_value(string|array $plate): mixed {
        $list = PHPlaterBase::ifJsonToArray($plate);
        return min($list);
    }

    public static function prev_key(string|array $plate, string|int $key): int|string {
        $list = PHPlaterBase::ifJsonToArray($plate);

        $keys = array_keys($list);
        $found_index = array_search($key, $keys);
        if ($found_index === false || $found_index === 0) {
            return false;
        }
        return $keys[$found_index - 1];
    }

    public static function next_key(string|array $plate, string|int $key): int|string {
        $list = PHPlaterBase::ifJsonToArray($plate);
        $keys = array_keys($list);
        $found_index = array_search($key, $keys);
        if ($found_index === false || $found_index === array_key_last($list)) {
            return false;
        }
        return $keys[$found_index];
    }

    public static function prev_value(string|array $plate, string|int $key): mixed {
        $list = PHPlaterBase::ifJsonToArray($plate);
        $keys = array_keys($list);
        $found_index = array_search($key, $keys);
        if ($found_index === false || $found_index === 0) {
            return false;
        }
        return $list[$keys[$found_index - 1]];
    }

    public static function next_value(string|array $plate, string|int $key): mixed {
        $list = PHPlaterBase::ifJsonToArray($plate);
        $keys = array_keys($list);
        $found_index = array_search($key, $keys);
        if ($found_index === false || $found_index === array_key_last($list)) {
            return false;
        }
        return $list[$keys[$found_index]];
    }

}
