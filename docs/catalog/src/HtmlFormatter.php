<?php
declare(strict_types=1);

namespace BasecoatTheme\Docs;

/**
 * Minimal HTML pretty-printer for the catalog "HTML" tab: one tag per line, indented by nesting.
 *
 * An element whose only content is text stays on one line (`<span class="badge">New</span>`).
 * The content of `pre`, `textarea`, `script` and `style` is kept verbatim.
 */
final class HtmlFormatter
{
    private const INDENT = '  ';

    private const VOID = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr',
    ];

    private const RAW = ['pre', 'textarea', 'script', 'style'];

    /**
     * Pretty-prints an HTML fragment.
     *
     * @param string $html HTML.
     * @return string
     */
    public static function pretty(string $html): string
    {
        $tokens = self::tokenize($html);
        $lines = [];
        $depth = 0;
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            $name = self::tagName($token);
            $pad = str_repeat(self::INDENT, $depth);

            if ($name === null) {
                $text = trim((string)preg_replace('/\s+/', ' ', $token));
                if ($text !== '') {
                    $lines[] = $pad . $text;
                }
                continue;
            }

            if (str_starts_with($token, '</')) {
                $depth = max(0, $depth - 1);
                $lines[] = str_repeat(self::INDENT, $depth) . $token;
                continue;
            }

            if (self::isSelfContained($token, $name)) {
                $lines[] = $pad . $token;
                continue;
            }

            if (in_array($name, self::RAW, true)) {
                $raw = $token;
                while (++$i < $count && self::tagName($tokens[$i]) !== $name) {
                    $raw .= $tokens[$i];
                }
                $raw .= $tokens[$i] ?? '';
                $lines[] = $pad . $raw;
                continue;
            }

            // <tag>text</tag> on one line.
            $next = $tokens[$i + 1] ?? null;
            $close = $tokens[$i + 2] ?? null;
            if ($next !== null && self::tagName($next) === null && $close === '</' . $name . '>') {
                $lines[] = $pad . $token . trim((string)preg_replace('/\s+/', ' ', $next)) . $close;
                $i += 2;
                continue;
            }
            if ($next === '</' . $name . '>') {
                $lines[] = $pad . $token . $next;
                $i++;
                continue;
            }

            $lines[] = $pad . $token;
            $depth++;
        }

        return implode("\n", $lines);
    }

    /**
     * Splits HTML into tags, comments and text.
     *
     * @param string $html HTML.
     * @return list<string>
     */
    private static function tokenize(string $html): array
    {
        $parts = preg_split(
            '/(<!--.*?-->|<\/?[a-zA-Z][^>]*>|<![^>]*>)/s',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
        );

        return $parts === false ? [$html] : array_values($parts);
    }

    /**
     * Lower-case tag name of a tag token, `!` for comments/doctype, null for text.
     *
     * @param string $token Token.
     * @return string|null
     */
    private static function tagName(string $token): ?string
    {
        if (str_starts_with($token, '<!')) {
            return '!';
        }
        if (preg_match('/^<\/?([a-zA-Z][a-zA-Z0-9-]*)/', $token, $match)) {
            return strtolower($match[1]);
        }

        return null;
    }

    /**
     * Whether a tag opens no element: void element, `/>`, comment or doctype.
     *
     * @param string $token Tag token.
     * @param string $name Tag name.
     * @return bool
     */
    private static function isSelfContained(string $token, string $name): bool
    {
        return $name === '!' || in_array($name, self::VOID, true) || str_ends_with($token, '/>');
    }
}
