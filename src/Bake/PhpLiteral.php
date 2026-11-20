<?php
declare(strict_types=1);

namespace BasecoatTheme\Bake;

use InvalidArgumentException;

/**
 * Writes values as PHP source for baked code: short arrays, 4-space indentation, one element per
 * line, lists without keys, single-quoted strings (only `\` and `'` escaped, UTF-8 kept as is;
 * double-quoted with escapes when the string has control characters other than `\t` and `\n`),
 * lowercase `null`/`true`/`false`. `eval('return ' . PhpLiteral::export($v) . ';') === $v`.
 */
final class PhpLiteral
{
    private const INDENT = '    ';

    /**
     * @param mixed $value Null, scalar or array of those.
     * @param int $indent Indentation level of the line the literal starts on: inner lines get
     *   one more level, the closing bracket this one.
     * @return string
     * @throws \InvalidArgumentException For objects, resources and other non-exportable values.
     */
    public static function export(mixed $value, int $indent = 0): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => var_export($value, true),
            is_string($value) => self::string($value),
            is_array($value) => self::array($value, $indent),
            default => throw new InvalidArgumentException(
                sprintf('Cannot write a `%s` as a PHP literal.', get_debug_type($value)),
            ),
        };
    }

    /**
     * @param array<mixed> $value Array.
     * @param int $indent Indentation level.
     * @return string
     */
    private static function array(array $value, int $indent): string
    {
        if ($value === []) {
            return '[]';
        }
        $isList = array_is_list($value);
        $pad = str_repeat(self::INDENT, $indent + 1);
        $lines = [];
        foreach ($value as $key => $item) {
            $prefix = $isList ? '' : self::export($key) . ' => ';
            $lines[] = $pad . $prefix . self::export($item, $indent + 1) . ',';
        }

        return "[\n" . implode("\n", $lines) . "\n" . str_repeat(self::INDENT, $indent) . ']';
    }

    /**
     * @param string $value String.
     * @return string
     */
    private static function string(string $value): string
    {
        if (!preg_match('/[\x00-\x08\x0B-\x1F\x7F]/', $value)) {
            return "'" . addcslashes($value, "\\'") . "'";
        }

        // Control characters: a double-quoted string with escapes, so the source stays readable.
        $escaped = preg_replace_callback(
            '/[\x00-\x1F\x7F"\\\\$]/',
            static fn (array $m): string => match ($m[0]) {
                "\n" => '\\n',
                "\t" => '\\t',
                "\r" => '\\r',
                '"', '\\', '$' => '\\' . $m[0],
                default => sprintf('\\x%02X', ord($m[0])),
            },
            $value,
        );

        return '"' . $escaped . '"';
    }
}
