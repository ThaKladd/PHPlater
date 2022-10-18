<?php

/**
 * The PHPlaterUnblock class
 *
 * This class manages the assining block within a template.
 *
 * @author  John Larsen
 * @license MIT
 */
class PHPlaterUnblock extends PHPlaterBase {

    /**
     * Get the pattern used to fetch all the includes in the template list
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        return self::buildPattern(Tag::UNBLOCK_BEFORE, '\s*(?P<x>.+?)\s*', Tag::UNBLOCK_AFTER);
    }

    /**
     * Finds the include variable and includes the content of the file
     *
     * @access public
     * @param  array<int|string, string> $match The matched regular expression from render
     * @param  PHPlater $core The core object
     * @return string The hash after holding the result
     */
    public function find(array $match, PHPlater $core): string {
        $hash = hash('xxh3', $match['x']);
        self::$hold['block'][$hash] = Tag::BLOCK_BEFORE->get(true) . trim($match['x']) . Tag::BLOCK_AFTER->get(true);
        return $hash;
    }

    /**
     * Goes through all the plates and replaces those that are in need of nesting and returns result
     *
     * @access public
     * @param  PHPlater $core The core object
     * @return string The end value
     */
    public function unblock(PHPlater $core): string {
        $plates = $core->getPlates();
        foreach ($plates as $plate => $plate_value) {
            if (str_contains($plate, Tag::BLOCK_BEFORE->get(true))) {
                foreach (self::$hold['block'] as $hash => $value) {
                    if (str_contains($plate_value, $hash)) {
                        $core->setPlate($plate, str_replace($hash, $core->getPlate($value), $plate_value));
                    }
                }
            }
        }
        $result = $core->getResult();
        foreach (self::$hold['block'] as $hash => $value) {
            $result = str_replace($hash, $core->getPlate($value), $result);
        }
        return $result;
    }

}
