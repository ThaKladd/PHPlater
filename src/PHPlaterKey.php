<?php

/**
 * The PHPlaterKey class
 *
 * This class manages the key within a list in PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */
use Error\RuleBrokenError;

class PHPlaterKey extends PHPlaterBase {
    
    /**
     * Creates PHPlaterKey object and initializes it
     *
     * @access public
     */
    public function __construct(PHPLater $phplater) {
        $this->core($phplater);
    }

    /**
     * Get the pattern used to fetch all the keys in the template list
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public function pattern(): string {
        $tag_list_key = preg_quote($this->tag(PHPlaterTag::TAG_LIST_KEY));
        return $this->buildPattern(PHPlaterTag::TAG_BEFORE, '\s*' . $tag_list_key . '\s*', PHPlaterTag::TAG_AFTER);
    }

}