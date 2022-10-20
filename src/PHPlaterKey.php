<?php

/**
 * The PHPlaterKey class
 *
 * This class manages the key within a list in PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */
class PHPlaterKey extends PHPlaterBase {

    /**
     * Get the pattern used to fetch all the keys in the template list
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        return self::buildPattern(Tag::BEFORE, '\s*' . Tag::LIST_KEY->get() . '' . Tag::FILTER->get() . '?(\w+)?\s*', Tag::AFTER);
    }

}
