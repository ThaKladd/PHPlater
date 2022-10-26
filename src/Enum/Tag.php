<?php

/**
 * All the tags in PHPLater and corresponding functionality
 * Quick ref here: https://en.wikipedia.org/wiki/Power_of_two
 * @author  John Larsen
 * @license MIT
 */
enum Tag: int {

    case BEFORE = 1;
    case AFTER = 2;
    case LIST_BEFORE = 4;
    case LIST_AFTER = 8;
    case LIST_KEY = 16;
    case CONDITIONAL_BEFORE = 32;
    case CONDITIONAL_AFTER = 64;
    case IF_CONDITIONAL = 128;
    case ELSE_CONDITIONAL = 256;
    case ARGUMENT = 512;
    case ARGUMENT_LIST = 1024;
    case CHAIN = 2048;
    case FILTER = 4096;
    case DELIMITER = 8192;
    case BLOCK_BEFORE = 16384;
    case BLOCK_AFTER = 32768;
    case EMPTY_ARRAY = 65536; //Undecided
    case EMPTY_STRING = 131072; //Undecided
    case INCLUDE_FILE = 262144;
    case INCLUDE_RENDER = 524288;  //Undecided - Maybe use filter?
    case ASSIGN = 1048576; //Undecided

    public function affectedClasses(): array {
        $return = match ($this) {
            self::BEFORE, self::AFTER => [ClassString::VARIABLE, ClassString::KEY],
            self::LIST_BEFORE, self::LIST_AFTER => [ClassString::LISTS],
            self::BLOCK_BEFORE, self::BLOCK_AFTER => [ClassString::BLOCK],
            self::CHAIN => [ClassString::LISTS],
            self::LIST_KEY => [ClassString::KEY],
            self::CONDITIONAL_BEFORE, self::CONDITIONAL_AFTER => [ClassString::CONDITIONAL],
            self::INCLUDE_FILE => [ClassString::INCLUDE_FILE],
            default => []
        };
        return $return;
    }

    /**
     * Set tag on enum, both the preg_quote and the raw version
     *
     * @access public
     * @param  string $tag The tag string
     * @return void
     */
    public function set(string $tag) {
        if ($this === self::DELIMITER) {
            if (strlen($tag) > 1) {
                throw new RuleBrokenError('Preg delimiter can not be over 1 in length.');
            } else if (ctype_alnum($tag) || $tag === '\\') {
                throw new RuleBrokenError('Preg Delimiter can not be alphanumeric or backslash.');
            }
        }
        PHPlaterBase::$tags[false][$this->value] = preg_quote($tag, self::DELIMITER->get(true));
        PHPlaterBase::$tags[true][$this->value] = $tag;
        $affected_classes = $this->affectedClasses();
        if ($affected_classes) {
            foreach ($affected_classes as $affected_class) {
                unset(PHPlaterBase::$instances[$affected_class->value]['pattern']);
            }
        }
        PHPlaterBase::$changed_tags = true;
    }

    /**
     * Get tag from enum
     *
     * @access public
     * @param  bool $raw If the clean not preg_quote version
     * @return string The tag
     */
    public function get(bool $raw = false): string {
        return PHPlaterBase::$tags[$raw][$this->value] ?? '';
    }

}
