<?php

/**
 * The PHPlaterBlock class
 *
 * This class manages the blocks within a template.
 *
 * @author  John Larsen
 * @license MIT
 */
class PHPlaterBlock extends PHPlaterBase {

    /**
     * Get the pattern used to fetch all the blocks in the template list
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        return self::buildPattern(Tag::BLOCK_BEFORE, '\s*(?P<x>.+?)\s*', Tag::BLOCK_AFTER);
    }

    /**
     * Finds the block variable and renders it in place
     *
     * @access public
     * @param  array<int|string, string> $match The matched regular expression from render
     * @param  PHPlater $core The core object
     * @return string The result after replacing
     */
    public function find(array $match, PHPlater $core): string {
        $exploded = explode(Tag::ASSIGN->get(true), $match['x']);
        $exploded_filter = explode(Tag::FILTER->get(true), $exploded[0]);
        if (isset($exploded[1])) {
            $core->setPlate(Tag::BLOCK_BEFORE->get(true) . trim($exploded_filter[0]) . Tag::BLOCK_AFTER->get(true), trim($exploded[1]));
            if (isset($exploded_filter[1]) && trim($exploded_filter[1]) == 'render') {
                return $exploded[1];
            }
            return '';
        }
        return $exploded_filter[0];
    }

}
