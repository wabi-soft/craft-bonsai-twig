# Entry Loader - Pure Twig Equivalent

How to replicate the `entryTemplates()` function using native Twig if you remove the plugin.

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{{ entryTemplates({ entry }) }}
```

## Pure Twig Equivalent

```twig
{% set entryPath = 'entry/' %}

{% include [
    entryPath ~ entry.section.handle ~ '/' ~ entry.type.handle ~ '/' ~ entry.slug,
    entryPath ~ entry.section.handle ~ '/' ~ entry.slug,
    entryPath ~ entry.section.handle ~ '/' ~ entry.type.handle,
    entryPath ~ entry.section.handle ~ '/default',
    entryPath ~ entry.section.handle,
    entryPath ~ entry.type.handle,
    entryPath ~ 'default'
] %}
```

## Template Path Resolution

The include checks templates in order (first match wins):

1. `entry/{section}/{type}/{slug}` - Exact entry match
2. `entry/{section}/{slug}` - Section + slug
3. `entry/{section}/{type}` - Entry type
4. `entry/{section}/default` - Section fallback
5. `entry/{section}` - Section only
6. `entry/{type}` - Type only
7. `entry/default` - Global fallback

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- Multi-site `baseSite` prefix support
- `_entry` fallback path (plugin adds `entry/{section}/{type}/_entry`)
