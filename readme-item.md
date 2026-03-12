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
{% set itemPath = 'item/' %}
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
{% set itemPath = 'item/' %}
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

1. `item/{section}/{style}` - Section + style
2. `item/{style}` - Style only
3. `item/{section}/{type}/{slug}` - Exact entry match
4. `item/{section}/{type}` - Entry type
5. `item/{section}/default` - Section fallback
6. `item/default` - Global fallback

### Type-first (`strategy: 'type'`)

1. `item/{type}/{style}` - Type + style
2. `item/{style}` - Style only
3. `item/{type}/{section}/{slug}` - Exact entry match
4. `item/{type}/{section}` - Type + section
5. `item/{type}/default` - Type fallback
6. `item/default` - Global fallback

### Context Paths with Strategy

When using `ctx`, the context element's dimensions also flip:

**Section-first:** `item/{section}/ctx/{contextSection}/{contextType}/...`

**Type-first:** `item/{type}/ctx/{contextType}/{contextSection}/...`

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- `ctx` context-aware path resolution
- Multi-site `baseSite` prefix support
- Additional context hierarchy paths (ctx/{section}/{type}/...)
- Strategy parameter with three-level configuration (per-template, config file, CP)
