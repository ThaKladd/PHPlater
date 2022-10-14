<?php

/**
 * The PHPlaterVariable class
 *
 * This class manages the variables within PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */
use Error\RuleBrokenError;

class PHPlaterVariable extends PHPlaterBase {

    /**
     * Get the pattern used to fetch all the variable tags in the template
     * \{\{\s*(?P<x>[\w,\-\|\.\:]+?)\s*\}\}
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        //$tags = preg_quote(Tag::FILTER_ > get() . Tag::ARGUMENT->get() . Tag::CHAIN->get() . Tag::ASSIGN->get());
        //$old = '[\w,\-' . $tags . ' ]+';
        return self::buildPattern(Tag::BEFORE, '\s*(?P<x>.+?)\s*', Tag::AFTER);
    }

    /**
     * Begins with the root plated and iterates through every one and extracts the matching value
     *
     * Each variable can be nested and in the end, the last vaulue is returned as the exhange for the match
     * Example: key.value_as_array.value_as_object.method_to_call
     *
     * @access public
     * @param  array<int|string, string> $match The matched regular expression from render
     * @param  PHPlater $core The core object
     * @return string The result after exchanging all the matched plates
     */
    public function find(array $match, PHPlater $core): string {
        if ($core->getMany()) {
            $all_plates = '';
            foreach ($core->getPlates() as $plates) {
                $all_plates .= (new PHPlater())->setPlates($plates)->render($core->getResult());
            }
            return $all_plates;
        }
        $exploded = explode(Tag::ASSIGN->get(true), $match['x']);
        if (isset($exploded[1])) {
            if (!trim($exploded[1])) {
                throw new RuleBrokenError('Cannot assign empty to variable.');
            } else if (!trim($exploded[0])) {
                throw new RuleBrokenError('Variable cannot be empty when setting.');
            }
            $core->setPlate(trim($exploded[0]), trim($exploded[1]));
            return '';
        }
        [$parts, $filters] = self::getFiltersAndParts($exploded[0]);
        $plate = $core->getPlate(array_shift($parts));
        foreach ($parts as $part) {
            $plate = self::extract(self::ifJsonToArray($plate), $part);
        }
        return ClassString::FILTER->object()->callFilters($plate, $filters);
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
        $tag_filter = Tag::FILTER->get(true);
        if (str_contains($plate, $tag_filter)) {
            $parts = explode($tag_filter, $plate);
            $plate = array_shift($parts);
        }
        $chain = [$plate];
        $tag_chain = Tag::CHAIN->get(true);
        if (str_contains($plate, $tag_chain)) {
            $chain = explode($tag_chain, $plate);
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
