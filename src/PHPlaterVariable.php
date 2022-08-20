<?php

/**
 * The PHPlaterConditional class
 *
 * This class manages the conditionals within PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */

class PHPlaterVariable extends PHPlaterBase {

    /**
     * Get the pattern used to fetch all the variable tags in the template
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public function pattern(): string {
        $tags = preg_quote($this->tag(PHPlaterTag::TAG_FILTER) . $this->tag(PHPlaterTag::TAG_ARGUMENT) . $this->tag(PHPlaterTag::TAG_CHAIN));
        return $this->buildPattern(PHPlaterTag::TAG_BEFORE, '\s*(?P<x>[\w,\-' . $tags . ']+?)\s*', PHPlaterTag::TAG_AFTER);
    }

    /**
     * Begins with the root plated and iterates through every one and extracts the matching value
     *
     * Each variable can be nested and in the end, the last vaulue is returned as the exhange for the match
     * Example: key.value_as_array.value_as_object.method_to_call
     *
     * @access public
     * @param  array $match The matched regular expression from render
     * @return string The result after exchanging all the matched plates
     */
    public function find(array $match): string {
        if ($this->core()->many()) {
            $all_plates = '';
            foreach ($this->core()->plates() as $plates) {
                $all_plates .= (new PHPlater())->plates($plates)->render($this->core()->result());
            }
            return $all_plates;
        }
        [$parts, $filters] = $this->getFiltersAndParts($match['x']);
        $plate = $this->core()->plate(array_shift($parts));
        foreach ($parts as $part) {
            $plate = $this->extract($this->ifJsonToArray($plate), $part);
        }
        return $this->core()->get(self::CLASS_FILTER)->callFilters($plate, $filters);
    }

    /**
     * Helper method to separate nesting and filters
     *
     * @access private
     * @param  string $plate The plate string
     * @return array Nesting parts and filters separated into array
     */
    private function getFiltersAndParts(string $plate): array {
        $parts = explode($this->tag(PHPlaterTag::TAG_FILTER), $plate);
        $first_part = array_shift($parts);
        return [explode($this->tag(PHPlaterTag::TAG_CHAIN), $first_part), $parts];
    }

    /**
     * Will manage the content so that it is a string when stored into data
     *
     * @access private
     * @param  mixed $plate The last plate to check action on
     * @param  string $part The key of the plate to extract value from
     * @return mixed The content of the plate, to be acted upon on the next variable depth
     */
    private function extract(object|array|string|int|float|bool|null $plate, string $part): mixed {
        $return = '';
        if (is_object($plate)) {
            if (method_exists($plate, $part)) {
                $return = call_user_func([$plate, $part]);
            } else if (is_a($plate, self::CLASS_BASE)) {
                $return = $plate->plate($part);
            } else if (property_exists($plate, $part) && isset($plate->$part)) {
                $return = $plate->$part;
            }
        } else if (isset($plate[$part])) {
            $return = $plate[$part];
        }
        return $return;
    }

}