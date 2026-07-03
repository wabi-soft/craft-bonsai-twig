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
 *     <!-- bonsai:trace v="1" nonce="c4f9" -->      (once per page when tracing is active)
 *     <!-- bonsai:start id="c4f9-3" tpl="_entry/blog/blogPost" type="entry" el="blogPost#64" section="blog" -->
 *     …rendered template output…
 *     <!-- bonsai:end id="c4f9-3" -->
 *
 * Pairs match on id (same-template nesting makes tpl-matched ends ambiguous).
 * Ids are prefixed with a per-process random nonce so page content cannot
 * forge plausible pairs; the marker carries the same nonce for verification.
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
     * Per-process monotonic id; pairs match on nonce + counter.
     */
    private static int $counter = 0;

    private static ?string $nonce = null;

    /**
     * The once-per-page marker: distinguishes "tracing off" from "page has no
     * Bonsai renders", and publishes the nonce that authenticates pair ids.
     */
    public static function marker(): string
    {
        return '<!-- bonsai:trace v="1" nonce="' . self::nonce() . '" -->';
    }

    /**
     * Wraps rendered content in a bonsai:start/end comment pair.
     *
     * @param string $content Rendered template output
     * @param array<string, string|array<string>|null> $attrs Attribute map (see ATTR_ORDER).
     *        Null values are omitted. Array values (tried) are pipe-joined —
     *        each token escaped individually, and esc() strips `|`, so a token
     *        can never contain the delimiter.
     */
    public static function wrap(string $content, array $attrs): string
    {
        $id = self::nonce() . '-' . ++self::$counter;

        $parts = ['id="' . $id . '"'];
        foreach (self::ATTR_ORDER as $key) {
            $value = $attrs[$key] ?? null;
            if ($value === null || $value === [] || $value === '') {
                continue;
            }
            $value = is_array($value)
                ? implode('|', array_map(self::esc(...), $value))
                : self::esc($value);
            $parts[] = $key . '="' . $value . '"';
        }

        $attrString = implode(' ', $parts);

        return "<!-- bonsai:start {$attrString} -->\n{$content}\n<!-- bonsai:end id=\"{$id}\" -->";
    }

    public static function reset(): void
    {
        self::$counter = 0;
        self::$nonce = null;
    }

    private static function nonce(): string
    {
        return self::$nonce ??= bin2hex(random_bytes(2));
    }

    /**
     * Neutralises sequences that could terminate the comment, forge attribute
     * boundaries, or collide with the tried delimiter. Removals run first so
     * they cannot re-form dashes ("->-" would otherwise become "--"); the dash
     * pass loops because a single replace can leave pairs ("---" -> "- --").
     * Lossy by design — consumers are told to glob when a path doesn't match.
     */
    private static function esc(string $value): string
    {
        $value = str_replace(['>', '"', '|'], '', $value);
        while (str_contains($value, '--')) {
            $value = str_replace('--', '- -', $value);
        }

        return $value;
    }
}
