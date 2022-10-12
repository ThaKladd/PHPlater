<?php

/**
 * The PHPlaterVariable class
 *
 * This class manages the variables within PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */

class PHPlaterVariable extends PHPlaterBase {

    /**
     * Get the pattern used to fetch all the variable tags in the template
     * \{\{\s*(?P<x>[\w,\-\|\.\:]+?)\s*\}\}
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        $tags = preg_quote(self::getTag(self::TAG_FILTER) . self::getTag(self::TAG_ARGUMENT) . self::getTag(self::TAG_CHAIN));
        return self::buildPattern(self::TAG_BEFORE, '\s*(?P<x>[\w,\-' . $tags . ']+?)\s*', self::TAG_AFTER);
    }

    /**
     * Begins with the root plated and iterates through every one and extracts the matching value
     *
     * Each variable can be nested and in the end, the last vaulue is returned as the exhange for the match
     * Example: key.value_as_array.value_as_object.method_to_call
     *
     * @access public
     * @param  array<int|string, string> $match The matched regular expression from render
     * @return string The result after exchanging all the matched plates
     */
    public function find(array $match): string {
        $core = $this->getCore();
        if ($core->getMany()) {
            $all_plates = '';
            foreach ($core->getPlates() as $plates) {
                $all_plates .= (new PHPlater())->setPlates($plates)->render($core->getResult());
            }
            return $all_plates;
        }

        [$parts, $filters] = self::getFiltersAndParts($match['x']);
        $plate = $core->getPlate(array_shift($parts));
        foreach ($parts as $part) {
            $plate = self::extract(self::ifJsonToArray($plate), $part);
        }
        return $core->getPHPlaterObject(self::CLASS_FILTER)->callFilters($plate, $filters);
    }

    /**
     * Helper method to separate nesting and filters
     *
     * @access private
     * @param  string $plate The plate string
     * @return array<mixed> Nesting parts and filters separated into array
     */
    private static function getFiltersAndParts(string $plate): array {
        $parts = []; //gives error or wrong result if this is []
        if (str_contains($plate, self::getTag(self::TAG_FILTER))) {
            $parts = explode(self::getTag(self::TAG_FILTER), $plate);
            $plate = array_shift($parts);
        }
        $chain = [$plate];
        if (str_contains($plate, self::getTag(self::TAG_CHAIN))) {
            $chain = explode(self::getTag(self::TAG_CHAIN), $plate);
        }
        return [$chain, $parts];
    }

    /**
     * Will manage the content so that it is a string when stored into data
     *
     * @access private
     * @param  object|array<string|int, mixed>|string|int|float|bool|null $plate The last plate to check action on
     * @param  string $part The key of the plate to extract value from
     * @return mixed The content of the plate, to be acted upon on the next variable depth
     */
    private static function extract(object|array|string|int|float|bool|null $plate, string $part): mixed {
        $return = '';
        if (is_object($plate)) {
            if (method_exists($plate, $part)) {
                $return = call_user_func([$plate, $part]);
            } else if ($plate instanceof PHPlater) {
                $return = $plate->getPlate($part);
            } else if (property_exists($plate, $part) && isset($plate->$part)) {
                $return = $plate->$part;
            }
        } else if (isset($plate[$part])) {
            $return = $plate[$part];
        }
        return $return;
    }

}