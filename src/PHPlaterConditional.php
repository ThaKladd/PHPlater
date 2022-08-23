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
     *
     * @access public
     * @return string The pattern for preg_replace_callback
     */
    public static function pattern(): string {
        return self::buildPattern(self::TAG_CONDITIONAL_BEFORE, '(?P<x>.+?)', self::TAG_CONDITIONAL_AFTER);
    }

    /**
     * Finds the conditionals and exchanges the position with the rendering and subsequent evaluation of values and then renders the result
     *
     * @access public
     * @param  array $match The matched regular expression from renderConditional
     * @return string The result after rendering all conditionals
     */
    public function find(array $match): string {
        $splitted_conditional = explode(self::getTag(self::TAG_IF), $match['x']);
        $condition = trim($splitted_conditional[0]);
        $rendered_condition = $this->doOpreration($condition);
        $splitted_if_else = explode(self::getTag(self::TAG_ELSE), $splitted_conditional[1]);
        $ifTrue = trim($splitted_if_else[0] ?? '');
        $ifFalse = trim($splitted_if_else[1] ?? '');
        return $this->getCore()->render($rendered_condition ? $ifTrue : $ifFalse);
    }

    /**
     * Does the render, renders operation if operand is found
     *
     * @param string $condition The condition of the conditional
     * @return bool|string|int
     */
    private function doOpreration(string $condition): bool|string|int {
        $operators = ['\={3}', '\={2}', '\!\={2}', '\!\={1}', '\<\=\>', '\>\=', '\<\=', '\<\>', '\>', '\<', '%', '&{2}', '\|{2}', 'xor', 'and', 'or'];
        if (preg_match('/.+\s*(' . implode('|', $operators) . ')\s*.+/U', $condition, $matches)) {
            $a_and_b = explode($matches[1], $condition);
            $a = trim($this->getCore()->render($a_and_b[0]));
            $b = trim($this->getCore()->render($a_and_b[1]));
            return self::evaluateOperation($a, $matches[1], $b);
        }
        return $this->getCore()->render($condition);
    }

    /**
     * Method to return value of operation when done with a matched string operand
     *
     * @access private
     * @param  string $a The first value
     * @param  string $operator The operand to evaluate first and second value with
     * @param  string $b The second value
     * @return bool|int The result after evaluating the values with the given operand
     */
    private static function evaluateOperation(string $a, string $operator, string $b): bool|int {
        $a = is_numeric($a) ? (int) $a : $a;
        $b = is_numeric($b) ? (int) $b : $b;
        $either_is_string = is_string($a) || is_string($b);
        $match = match ($operator) {
            '==' => $a == $b,
            '!==' => $a !== $b,
            '===' => $a === $b,
            '!=' => $a != $b,
            '>=' => $a >= $b,
            '<=' => $a <= $b,
            '>' => $a > $b,
            '<' => $a < $b,
            '<>' => $a <> $b,
            '<=>' => $a <=> $b,
            '&&', 'and' => $a && $b,
            '||', 'or' => $a || $b,
            'xor' => $a xor $b,
            '%' => ($either_is_string ? throw new RuleBrokenError('Modulo can only be used with numbers. Values are "' . $a . '" and "' . $b . '".') : (int) $a % (int) $b),
            default => throw new RuleBrokenError('Found no matching operator for "' . $operator . '".')
        };
        return $match;
    }
}