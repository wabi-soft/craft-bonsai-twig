<?php

namespace wabisoft\bonsaitwig\debug;

/**
 * Builds LLM trace comments bracketing Bonsai-resolved template output.
 *
 * Pure string building, no Craft dependencies. Emission is gated by
 * BonsaiTwig::traceEnabled() at the call site — this class never decides
 * whether to emit.
 *
 * Comment grammar:
 *
 *     <!-- bonsai:start id="3" tpl="_entry/blog/blogPost" type="entry" el="blogPost#64" section="blog" -->
 *     …rendered template output…
 *     <!-- bonsai:end id="3" -->
 *
 * Pairs match on id (same-template nesting makes tpl-matched ends ambiguous).
 * Attribute values carry template paths, ids, and handles only — never field
 * values.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\debug
 * @since 9.3.0
 */
class TraceComment
{
    /**
     * Attribute output order; keys not listed here are dropped.
     */
    private const ATTR_ORDER = ['tpl', 'type', 'el', 'section', 'block', 'strategy', 'tried'];

    /**
     * Per-request monotonic id; pairs match on it.
     */
    private static int $counter = 0;

    /**
     * Wraps rendered content in a bonsai:start/end comment pair.
     *
     * @param string $content Rendered template output
     * @param array<string, string|array<string>|null> $attrs Attribute map (see ATTR_ORDER).
     *        Null values are omitted; array values (tried) are space-joined.
     */
    public static function wrap(string $content, array $attrs): string
    {
        $id = ++self::$counter;

        $parts = ['id="' . $id . '"'];
        foreach (self::ATTR_ORDER as $key) {
            $value = $attrs[$key] ?? null;
            if ($value === null || $value === [] || $value === '') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $parts[] = $key . '="' . self::esc($value) . '"';
        }

        $attrString = implode(' ', $parts);

        return "<!-- bonsai:start {$attrString} -->\n{$content}\n<!-- bonsai:end id=\"{$id}\" -->";
    }

    /**
     * Neutralises sequences that could terminate the comment or its attributes.
     */
    private static function esc(string $value): string
    {
        return str_replace(['--', '>', '"'], ['- -', '', ''], $value);
    }
}
