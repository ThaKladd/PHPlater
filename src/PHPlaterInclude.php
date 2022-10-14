<?php

/**
 * The PHPlaterInclude class
 *
 * This class manages the includes within a template.
 *
 * @author  John Larsen
 * @license MIT
 */
class PHPlaterInclude extends PHPlaterBase {

    /**
     * Get the pattern used to fetch all the includes in the template list
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        Tag::DELIMITER->set('^');
        return self::buildPattern(Tag::INCLUDE_FILE, '\s*(?P<x>.+?)\s*', Tag::INCLUDE_FILE);
    }

    /**
     * Finds the include variable and includes the content of the file
     *
     * @access public
     * @param  array<int|string, string> $match The matched regular expression from render
     * @return string The result after rendering all includes
     */
    public function find(array $match): string {
        $phplater = clone $this->core;
        $exploded = explode(Tag::FILTER->get(true), $match['x']);
        $data = $phplater->setContent($exploded[0])->getContent();
        if (isset($exploded[1])) {
            if ($exploded[1] == 'render') {
                $data = $phplater->render($data);
            }
        }
        return $data;
    }

}
