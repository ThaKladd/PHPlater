<?php

/**
 * The PHPlaterList class
 *
 * This class manages the lists within PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */

class PHPlaterList extends PHPlaterBase {

    /**
     * Finds and returns the list from the location ['one', 'two', 'three'] in plates
     *
     * @access private
     * @param array $plates The plates array
     * @param type $array List of values to iterate in plates
     * @return array
     */
    private static function getList(array $plates, array $array = []): string|array {
        $key = array_shift($array);
        if ($array) {
            return self::getList($plates[$key], $array);
        }
        return $key === '' ? $plates : $plates[$key];
    }

    /**
     * Finds the list variable and exchanges the position with the keys, and then renders the result
     *
     * @access public
     * @param  array $match The matched regular expression from renderList
     * @return string The result after rendering all elements in the list
     */
    public function find(array $match): string {
        preg_match_all($this->core()->get(self::CLASS_VARIABLE)->pattern(), $match['x'], $matches);
        $tag_chain = self::tag(self::TAG_CHAIN);
        $key_pattern = $this->core()->get(self::CLASS_KEY)->pattern();
        $tag_list = $tag_chain . $tag_chain;
        $all_before_parts = explode($tag_list, $matches['x'][0]);
        $tag_last = end($all_before_parts) === '' ? '' : $tag_chain;
        $tag_first = reset($all_before_parts) === '' ? '' : $tag_chain;
        $core_parts = explode($tag_chain, $all_before_parts[0]);
        $elements = '';
        $list = self::getList($this->core()->plates(), $core_parts);
        foreach ($list as $key => $item) {
            $replaced_template = strtr($match['x'], [$tag_list => $tag_first . $key . $tag_last]);
            $new_template = self::replaceKeys($replaced_template, $key, $key_pattern);
            $elements .= $this->core()->render($new_template);
        }
        return $elements;
    }

    /**
     * Get the pattern used to fetch all the variable tags in the template
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        $tag_chain = preg_quote(self::tag(self::TAG_CHAIN));
        return self::buildPattern(self::TAG_LIST_BEFORE, '(?P<x>.+' . $tag_chain . $tag_chain . '.+?)', self::TAG_LIST_AFTER);
    }

    /**
     * Replaces the key tags with the current key in list
     *
     * @param string $template The list part of the template
     * @param string $key The key for the current iteration of the list
     * @param string $pattern the pattern of how to match the key
     * @return string
     */
    private static function replaceKeys(string $template, string $key, string $pattern): string {
        if (preg_match_all($pattern, $template, $key_matches) > 0) {
            foreach (array_unique($key_matches[0]) as $key_match) {
                $template = strtr($template, [$key_match => $key]);
            }
        }
        return $template;
    }
}