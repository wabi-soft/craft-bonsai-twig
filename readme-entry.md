# Entry Loader - Pure Twig Equivalent

How to replicate the `entryTemplates()` function using native Twig if you remove the plugin.

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{# Section-first (default) #}
{{ entryTemplates({ entry }) }}

{# Type-first (v8.0) #}
{{ entryTemplates({ entry, strategy: 'type' }) }}
```

## Pure Twig Equivalent

### Section-first (default)

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

### Type-first (v8.0)

```twig
{% set entryPath = 'entry/' %}

{% include [
    entryPath ~ entry.type.handle ~ '/' ~ entry.section.handle ~ '/' ~ entry.slug,
    entryPath ~ entry.type.handle ~ '/' ~ entry.section.handle ~ '/_entry',
    entryPath ~ entry.type.handle ~ '/' ~ entry.slug,
    entryPath ~ entry.type.handle ~ '/' ~ entry.section.handle,
    entryPath ~ entry.type.handle ~ '/default',
    entryPath ~ entry.type.handle,
    entryPath ~ entry.section.handle,
    entryPath ~ 'default'
] %}
```

## Template Path Resolution

### Section-first (default: `strategy: 'section'`)

1. `entry/{section}/{type}/{slug}` - Exact entry match
2. `entry/{section}/{slug}` - Section + slug
3. `entry/{section}/{type}` - Entry type
4. `entry/{section}/default` - Section fallback
5. `entry/{section}` - Section only
6. `entry/{type}` - Type only
7. `entry/default` - Global fallback

### Type-first (`strategy: 'type'`)

1. `entry/{type}/{section}/{slug}` - Exact entry match
2. `entry/{type}/{section}/_entry` - Type + section fallback
3. `entry/{type}/{slug}` - Type + slug
4. `entry/{type}/{section}` - Type + section
5. `entry/{type}/default` - Type fallback
6. `entry/{type}` - Type only
7. `entry/{section}` - Section only
8. `entry/default` - Global fallback

## Per-Section Strategy

You can vary the strategy by section so that some sections resolve type-first while others stay section-first:

```twig
{% set strategy = entry.section.handle in ['blog', 'resources'] ? 'type' : 'section' %}
{{ entryTemplates({ entry: entry, strategy: strategy }) }}
```

Pure Twig equivalent:

```twig
{% set entryPath = 'entry/' %}
{% if entry.section.handle in ['blog', 'resources'] %}
    {# type-first #}
    {% include [
        entryPath ~ entry.type.handle ~ '/' ~ entry.section.handle ~ '/' ~ entry.slug,
        entryPath ~ entry.type.handle ~ '/' ~ entry.section.handle ~ '/_entry',
        entryPath ~ entry.type.handle ~ '/' ~ entry.slug,
        entryPath ~ entry.type.handle ~ '/' ~ entry.section.handle,
        entryPath ~ entry.type.handle ~ '/default',
        entryPath ~ entry.type.handle,
        entryPath ~ entry.section.handle,
        entryPath ~ 'default'
    ] %}
{% else %}
    {# section-first #}
    {% include [
        entryPath ~ entry.section.handle ~ '/' ~ entry.type.handle ~ '/' ~ entry.slug,
        entryPath ~ entry.section.handle ~ '/' ~ entry.slug,
        entryPath ~ entry.section.handle ~ '/' ~ entry.type.handle,
        entryPath ~ entry.section.handle ~ '/default',
        entryPath ~ entry.section.handle,
        entryPath ~ entry.type.handle,
        entryPath ~ 'default'
    ] %}
{% endif %}
```

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- Multi-site `baseSite` prefix support
- `_entry` fallback path (plugin adds `entry/{section}/{type}/_entry`)
- Strategy parameter with three-level configuration (per-template, config file, CP)
