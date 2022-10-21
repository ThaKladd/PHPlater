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
     * Get the pattern used to fetch all the variable tags in the template
     * \[\[\s*(?P<x>[\w\W]+?\.\.[\w\W]+?)\s*\]\]
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        $tag_chain = Tag::CHAIN->get();
        return self::buildPattern(Tag::LIST_BEFORE, '\s*(?P<x>[\w\W]+?' . $tag_chain . $tag_chain . '[\w\W]+?)\s*', Tag::LIST_AFTER);
    }

    /**
     * Finds and returns the list from the location ['one', 'two', 'three'] in plates
     *
     * @access private
     * @param array<mixed> $plates The plates array
     * @param array<mixed> $array List of values to iterate in plates
     * @return array<string, mixed>
     */
    private static function getList(array $plates, array $array = []): array {
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
     * @param  array<int|string, string> $match The matched regular expression from renderList
     * @param  PHPlater $core The core object
     * @return string The result after rendering all elements in the list
     */
    public function find(array $match, PHPlater $core): string {
        $variable_pattern = ClassString::VARIABLE->pattern();
        preg_match_all($variable_pattern, $match['x'], $matches);

        //Because a variable can have any cahracter, the key has to be filtered out and not match the character
        $matches_changed = false;
        if (count($matches['x']) > 1) {
            $tag_key = Tag::LIST_KEY->get(true);
            foreach ($matches['x'] as $key => $value) {
                if (str_contains($value, $tag_key)) {
                    $matches_changed = true;
                    unset($matches['x'][$key]);
                }
            }
            if ($matches_changed) {
                $matches['x'] = array_values($matches['x']); //Rebuild array indexes
            }
        }

        $tag_chain = Tag::CHAIN->get(true);
        $key_pattern = ClassString::KEY->pattern();
        $tag_list = $tag_chain . $tag_chain;
        $all_before_parts = explode($tag_list, $matches['x'][0]);
        $tag_last = end($all_before_parts) === '' ? '' : $tag_chain;
        $tag_first = reset($all_before_parts) === '' ? '' : $tag_chain;
        $core_parts = explode($tag_chain, $all_before_parts[0]);
        $elements = '';
        $list = self::getList($core->getPlates(), $core_parts);
        foreach ($list as $key => $item) {
            $replaced_template = strtr($match['x'], [$tag_list => $tag_first . $key . $tag_last]);
            if ($matches_changed) {
                $replaced_template = self::replaceKeys($replaced_template, $key_pattern, $key, $list);
            }
            $elements .= $core->render($replaced_template);
        }
        return $elements;
    }

    /**
     * Replaces the key tags with the current key in list
     *
     * @param string $template The list part of the template
     * @param string $pattern the pattern of how to match the key
     * @param string $key The key for the current iteration of the list
     * @param string $list The key for the current iteration of the list
     * @return string
     */
    private static function replaceKeys(string $template, string $pattern, string $key, array $list): string {
        if (preg_match_all($pattern, $template, $key_matches) > 0) {
            foreach (array_unique($key_matches[0]) as $key_match) {
                $exploded_filter = explode(Tag::FILTER->get(true), $key_match);
                $return_key = $key;
                if (isset($exploded_filter[1])) {
                    $filter = trim($exploded_filter[1], ' ' . Tag::BEFORE->get() . Tag::AFTER->get());
                    //Todo: These will be moved to the PHPlaterFilter object
                    $return_key = match ($filter) {
                        'value' => $list[$key],
                        'first' => array_key_first($list),
                        'last' => array_key_last($list),
                        'first_value' => $list[array_key_first($list)],
                        'last_value' => $list[array_key_last($list)],
                        'count' => count($list),
                        'max' => array_search(max($list), $list),
                        'min' => array_search(min($list), $list),
                        'max_value' => max($list),
                        'min_value' => min($list),
                        'prev' => call_user_func(function ($list) use ($key) {
                            $keys = array_keys($list);
                            $found_index = array_search($key, $keys);
                            if ($found_index === false || $found_index === 0) {
                                return false;
                            }
                            return $keys[$found_index - 1];
                        }, $list),
                        'next' => call_user_func(function ($list) use ($key) {
                            $keys = array_keys($list);
                            $found_index = array_search($key, $keys);
                            if ($found_index === false || $found_index === array_key_last($list)) {
                                return false;
                            }
                            return $keys[$found_index];
                        }, $list),
                        'prev_value' => call_user_func(function ($list) use ($key) {
                            $keys = array_keys($list);
                            $found_index = array_search($key, $keys);
                            if ($found_index === false || $found_index === 0) {
                                return false;
                            }
                            return $list[$keys[$found_index - 1]];
                        }, $list),
                        'next_value' => call_user_func(function ($list) use ($key) {
                            $keys = array_keys($list);
                            $found_index = array_search($key, $keys);
                            if ($found_index === false || $found_index === array_key_last($list)) {
                                return false;
                            }
                            return $list[$keys[$found_index]];
                        }, $list),
                        default => throw new RuleBrokenError('Filter "' . $filter . '" to key "' . Tag::CHAIN->get() . '" does not exist.')
                    };
                }
                $template = strtr($template, [$key_match => $return_key]);
            }
            return $template;
        }
    }

}
