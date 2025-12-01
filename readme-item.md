# Item Loader - Pure Twig Equivalent

How to replicate the `itemTemplates()` function using native Twig if you remove the plugin.

## With Plugin

```twig
{#
While in devMode:

To see full path: ?showItemHierarchy=1
To see applied template: ?showItemPath=1
#}

{{ itemTemplates({ entry, style }) }}
```

## Pure Twig Equivalent

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

## Template Path Resolution

The include checks templates in order (first match wins):

1. `item/{section}/{style}` - Section + style
2. `item/{style}` - Style only
3. `item/{section}/{type}/{slug}` - Exact entry match
4. `item/{section}/{type}` - Entry type
5. `item/{section}/default` - Section fallback
6. `item/default` - Global fallback

## What You Lose

- Debug output (`?showItemHierarchy=1`, `?showItemPath=1`)
- `ctx` context-aware path resolution
- `options` and `overridableSettings` support
- Multi-site `baseSite` prefix support
- Additional context hierarchy paths (ctx/{section}/{type}/...)
