# Item Loader - Pure Twig Equivalent

How to replicate the `itemTemplates()` function using native Twig if you remove the plugin.

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{# Section-first (default) #}
{{ itemTemplates({ entry, style }) }}

{# Type-first (v8.0) #}
{{ itemTemplates({ entry, style, strategy: 'type' }) }}
```

## Pure Twig Equivalent

### Section-first (default)

```twig
{% set itemPath = '_item/' %}
{% set style = style is defined ? style : 'none' %}

{% include [
    itemPath ~ entry.section.handle ~ '/' ~ style,
    itemPath ~ style,
    itemPath ~ entry.section.handle ~ '/' ~ entry.type.handle ~ '/' ~ entry.slug,
    itemPath ~ entry.section.handle ~ '/' ~ entry.type.handle,
    itemPath ~ entry.section.handle ~ '/default',
    itemPath ~ 'default'
] ignore missing %}
```

### Type-first (v8.0)

```twig
{% set itemPath = '_item/' %}
{% set style = style is defined ? style : 'none' %}

{% include [
    itemPath ~ entry.type.handle ~ '/' ~ style,
    itemPath ~ style,
    itemPath ~ entry.type.handle ~ '/' ~ entry.section.handle ~ '/' ~ entry.slug,
    itemPath ~ entry.type.handle ~ '/' ~ entry.section.handle,
    itemPath ~ entry.type.handle ~ '/default',
    itemPath ~ 'default'
] ignore missing %}
```

## Template Path Resolution

### Section-first (default: `strategy: 'section'`)

1. `_item/{section}/{style}` - Section + style
2. `_item/{style}` - Style only
3. `_item/{section}/{type}/{slug}` - Exact entry match
4. `_item/{section}/{type}` - Entry type
5. `_item/{section}/default` - Section fallback
6. `_item/default` - Global fallback

### Type-first (`strategy: 'type'`)

1. `_item/{type}/{style}` - Type + style
2. `_item/{style}` - Style only
3. `_item/{type}/{section}/{slug}` - Exact entry match
4. `_item/{type}/{section}` - Type + section
5. `_item/{type}/default` - Type fallback
6. `_item/default` - Global fallback

### Context Paths with Strategy

When using `ctx`, the context element's dimensions also flip:

**Section-first:** `_item/{section}/ctx/{contextSection}/{contextType}/...`

**Type-first:** `_item/{type}/ctx/{contextType}/{contextSection}/...`

## Per-Section Strategy

You can vary the strategy by section so that some sections resolve type-first while others stay section-first:

```twig
{% set strategy = item.section.handle in ['blog', 'resources'] ? 'type' : 'section' %}
{{ itemTemplates({ entry: item, strategy: strategy }) }}
```

Pure Twig equivalent:

```twig
{% set itemPath = '_item/' %}
{% set style = style is defined ? style : 'none' %}
{% if entry.section.handle in ['blog', 'resources'] %}
    {# type-first #}
    {% include [
        itemPath ~ entry.type.handle ~ '/' ~ style,
        itemPath ~ style,
        itemPath ~ entry.type.handle ~ '/' ~ entry.section.handle ~ '/' ~ entry.slug,
        itemPath ~ entry.type.handle ~ '/' ~ entry.section.handle,
        itemPath ~ entry.type.handle ~ '/default',
        itemPath ~ 'default'
    ] ignore missing %}
{% else %}
    {# section-first #}
    {% include [
        itemPath ~ entry.section.handle ~ '/' ~ style,
        itemPath ~ style,
        itemPath ~ entry.section.handle ~ '/' ~ entry.type.handle ~ '/' ~ entry.slug,
        itemPath ~ entry.section.handle ~ '/' ~ entry.type.handle,
        itemPath ~ entry.section.handle ~ '/default',
        itemPath ~ 'default'
    ] ignore missing %}
{% endif %}
```

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- `ctx` context-aware path resolution
- Multi-site `baseSite` prefix support
- Additional context hierarchy paths (ctx/{section}/{type}/...)
- Strategy parameter with three-level configuration (per-template, config file, CP)
