<?php

/**
 * All the operators in PHPLater and methods that make use of them
 *
 * @author  John Larsen
 * @license MIT
 */
use Error\RuleBrokenError;

enum Operator: string {

    /**
     * The order of these matters in the regex below
     */
    case EQUALS_EXACT = '===';
    case EQUALS = '==';
    case EQUALS_NOT_EXACT = '!==';
    case EQUALS_NOT = '!=';
    case SPACESHIP = '<=>';
    case GREATER_THAN_OR_EQUAL = '>=';
    case LESS_THAN_OR_EQUAL = '<=';
    case EQUALS_NOT_ALTERNATIVE = '<>';
    case GREATER_THAN = '>';
    case LESS_THAN = '<';
    case MODULO = '%';
    case LOGICAL_AND = '&&';
    case LOGICAL_OR = '||';
    case LOGICAL_XOR = 'xor';
    case LOGICAL_AND_ALTERNATIVE = 'and';
    case LOGICAL_OR_ALTERNATIVE = 'or';
    case ARRAY_KEY_EXIST = 'key in';
    case ARRAY_IN = 'in';

    /**
     * Method to return value of operation when done with a matched string operand
     *
     * @access private
     * @param  string $a The first value
     * @param  string $b The second value
     * @return bool|int The result after evaluating the values with the given operand
     */
    public function evaluate(string $a, string $b): bool|int {
        $a = is_numeric($a) ? (int) $a : $a;
        $b = is_numeric($b) ? (int) $b : $b;
        $either_is_string = is_string($a) || is_string($b);
        $match = match ($this) {
            self::EQUALS => $a == $b,
            self::EQUALS_NOT_EXACT => $a !== $b,
            self::EQUALS_EXACT => $a === $b,
            self::EQUALS_NOT => $a != $b,
            self::GREATER_THAN_OR_EQUAL => $a >= $b,
            self::LESS_THAN_OR_EQUAL => $a <= $b,
            self::GREATER_THAN => $a > $b,
            self::LESS_THAN => $a < $b,
            self::EQUALS_NOT_ALTERNATIVE => $a <> $b,
            self::SPACESHIP => $a <=> $b,
            self::LOGICAL_AND, self::LOGICAL_AND_ALTERNATIVE => $a && $b,
            self::LOGICAL_OR, self::LOGICAL_OR_ALTERNATIVE => $a || $b,
            self::LOGICAL_XOR => $a xor $b,
            self::ARRAY_IN => in_array($a, PHPlaterBase::ifJsonToArray($b)),
            self::ARRAY_KEY_EXIST => key_exists($a, PHPlaterBase::ifJsonToArray($b)),
            self::MODULO => ($either_is_string ? throw new RuleBrokenError('Modulo can only be used with numbers. Values are "' . $a . '" and "' . $b . '".') : (int) $a % (int) $b),
            default => throw new RuleBrokenError('Found no matching operator for "' . $this->value . '".')
        };
        return $match;
    }

    /**
     * Does the regex operation on the condition, for find both sides of the operator
     *
     * @param string $condition The condition to match on
     * @return array Regex matches
     */
    public static function regex(string $condition): array {
        $pattern = PHPlaterBase::$instances[__CLASS__]['pattern'] ?? null;
        if (!$pattern) {
            $operators = [];
            foreach (self::cases() as $case) {
                $operators[] = preg_quote($case->value);
            }
            $pattern = '/.+\s*(' . implode('|', $operators) . ')\s*.+/U';
            PHPlaterBase::$instances[__CLASS__]['pattern'] = $pattern;
        }

        preg_match($pattern, $condition, $matches);
        return $matches;
    }

}
