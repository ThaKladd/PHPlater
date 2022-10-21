<?php

/**
 * The PHPlaterConditional class
 *
 * This class manages the conditionals within PHPlater.
 *
 * @author  John Larsen
 * @license MIT
 */
use Error\RuleBrokenError;

class PHPlaterConditional extends PHPlaterBase {

    /**
     * Get the pattern used for conditional
     * \(\(\s*(?P<x>.+?)\s*\)\)
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        return self::buildPattern(Tag::CONDITIONAL_BEFORE, '\s*(?P<x>.+?)\s*', Tag::CONDITIONAL_AFTER);
    }

    /**
     * Finds the conditionals and exchanges the position with the rendering and subsequent evaluation of values and then renders the result
     *
     * @access public
     * @param  array<int|string, string> $match The matched regular expression from renderConditional
     * @param  PHPlater $core The core object
     * @return string The result after rendering all conditionals
     */
    public function find(array $match, PHPlater $core): string {
        $splitted_conditional = explode(Tag::IF_CONDITIONAL->get(true), $match['x']);
        $condition = trim($splitted_conditional[0]);
        $rendered_condition = $this->doOpreration($condition, $core);
        $splitted_if_else = explode(Tag::ELSE_CONDITIONAL->get(true), $splitted_conditional[1]);
        $ifTrue = trim($splitted_if_else[0] ?? '');
        $ifFalse = trim($splitted_if_else[1] ?? '');
        return $core->render($rendered_condition ? $ifTrue : $ifFalse);
    }

    /**
     * Does the render, renders operation if operand is found
     *
     * @param string $condition The condition of the conditional
     * @return bool|string|int
     */
    private function doOpreration(string $condition, PHPlater $core): bool|string|int {
        $matches = Operator::regex($condition);
        if ($matches) {
            $a_and_b = explode($matches[1], $condition);
            $a = trim($core->render($a_and_b[0]));
            $b = trim($core->render($a_and_b[1]));
            return Operator::from(trim($matches[1]))->evaluate($a, $b);
        }
        return $core->render($condition);
    }

}
