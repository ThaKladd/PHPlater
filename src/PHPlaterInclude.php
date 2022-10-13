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
        self::setTag(self::TAG_DELIMITER, '^');
        return self::buildPattern(self::TAG_INCLUDE, '\s*(?P<x>.+?)\s*', self::TAG_INCLUDE);
    }

    /**
     * Finds the include variable and includes the content of the file
     *
     * @access public
     * @param  array<int|string, string> $match The matched regular expression from render
     * @return string The result after rendering all includes
     */
    public function find(array $match): string {
        $phplater = clone $this->getCore();
        $filter_tag = self::getTag(self::TAG_FILTER);
        $exploded = explode($filter_tag, $match['x']);
        $data = $phplater->setContent($exploded[0])->getContent();
        if (isset($exploded[1])) {
            if ($exploded[1] == 'render') {
                $data = $phplater->render($data);
            }
        }
        return $data;
    }

}