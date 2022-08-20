<?php

/**
 * The PHPlaterTag class
 *
 * This class manages the tags within PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */
use Error\RuleBrokenError;

class PHPlaterTag {

    const TAG_BEFORE = 0;
    const TAG_AFTER = 1;
    const TAG_LIST_BEFORE = 2;
    const TAG_LIST_AFTER = 3;
    const TAG_LIST_KEY = 4;
    const TAG_CONDITIONAL_BEFORE = 5;
    const TAG_CONDITIONAL_AFTER = 6;
    const TAG_IF = 7;
    const TAG_ELSE = 8;
    const TAG_ARGUMENT = 9;
    const TAG_ARGUMENT_LIST = 10;
    const TAG_CHAIN = 11;
    const TAG_FILTER = 12;
    const TAG_DELIMITER = 13;

    public $tags = [];

    /**
     * Set both template variable tags in one method
     *
     * Change tags if the current(default {{ and }}) tags are part of template
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  string $before Tag before variable in template
     * @param  string $after Tag after variable in template
     * @return self The current object
     */
    public function tagsVariables(string $before, string $after): PHPlaterTag {
        return $this->tags([
            self::TAG_BEFORE => preg_quote($before),
            self::TAG_AFTER => preg_quote($after)
        ]);
    }
   
    /**
     * Set both conditional tags in one method
     *
     * Change tags if the current(default (( and ))) tags are part of template
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  string $before Tag before conditional template in template
     * @param  string $after Tag after conditional template in template
     * @return self The current object
     */
    public function tagsConditionals(string $before, string $after): PHPlaterTag {
        return $this->tags([
            self::TAG_CONDITIONAL_BEFORE => preg_quote($before),
            self::TAG_CONDITIONAL_AFTER => preg_quote($after)
        ]);
    }

    /**
     * Set both list tags in one method
     *
     * Change tags if the current(default [[ and ]]) tags are part of template
     * Make sure there are no conflicts with the other tags
     *
     * @access public
     * @param  string $before Tag before list template in template
     * @param  string $after Tag after list template in template
     * @return self The current object
     */
    public function tagsList(string $before, string $after): PHPlaterTag {
        return $this->tags([
            self::TAG_LIST_BEFORE => preg_quote($before),
            self::TAG_LIST_AFTER => preg_quote($after)
        ]);
    }

    /**
     * Set all tags you want in one method or get all tags that are set
     *
     * @access public
     * @param  array $tags an array with constant as key, and tag as value
     * @return mixed The current object or an array with all the tags
     */
    public function tags(null|array $tags = null): array|PHPlaterTag {
        if ($tags === null) {
            return $this->tags;
        }
        foreach ($tags as $const => $tag) {
            $this->tag($const, $tag);
        }
        return $this;
    }

     /**
     * Set or get tag by a constant
     *
     * @access public
     * @param  string $tag_constant The constant to set or get tag with
     * @param  string $tag The tag string, if you want to set the tag
     * @return mixed The current object if a set, the string tag if it is get
     */
    public function tag(int $tag_constant, string|null $tag = null): string|PHPlaterTag {
        if ($tag === null) {
            return $this->tags[$tag_constant] ?? '';
        }
        if ($tag_constant == self::TAG_DELIMITER) {
            if (strlen($tag) > 1) {
                throw new RuleBrokenError('Preg delimiter can not be over 1 in length.');
            } else if (ctype_alnum($tag) || $tag == '\\') {
                throw new RuleBrokenError('Preg Delimiter can not be alphanumeric or backslash.');
            }
        }
        $this->tags[$tag_constant] = $tag;
        return $this;
    }

}