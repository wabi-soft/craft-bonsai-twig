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
 *     <!-- bonsai:start id="3" tpl="default/_entry/foo/default" type="entry" el="foo#1234" -->
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
     * Per-request monotonic id; pairs match on it.
     */
    private static int $counter = 0;

    /**
     * Wraps rendered content in a bonsai:start/end comment pair.
     *
     * @param string $content Rendered template output
     * @param string $tpl Resolved (winning) template path
     * @param string $type Template type (entry, item, matrix, category, product, asset)
     * @param string|null $el "<typeOrGroupHandle>#<elementId>", omitted when null
     * @param string $strategy Resolution strategy; attribute omitted for the default 'section'
     */
    public static function wrap(string $content, string $tpl, string $type, ?string $el, string $strategy): string
    {
        $id = ++self::$counter;

        $attrs = 'id="' . $id . '" tpl="' . self::esc($tpl) . '" type="' . self::esc($type) . '"';
        if ($el !== null) {
            $attrs .= ' el="' . self::esc($el) . '"';
        }
        if ($strategy !== 'section') {
            $attrs .= ' strategy="' . self::esc($strategy) . '"';
        }

        return "<!-- bonsai:start {$attrs} -->\n{$content}\n<!-- bonsai:end id=\"{$id}\" -->";
    }

    /**
     * Neutralises sequences that could terminate the comment or its attributes.
     */
    private static function esc(string $value): string
    {
        return str_replace(['--', '>', '"'], ['- -', '', ''], $value);
    }
}
