# Matrix Loader - Pure Twig Equivalent

How to replicate the `matrixTemplates()` function using native Twig if you remove the plugin.

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{% if matrix|length %}
    {% set style = style ?? null %}
    {% for block in matrix.collect() ?? null %}
        {{ matrixTemplates({
            block: block,
            style: style,
            ctx: entry ?? null,
            next: block.next.type ?? false,
            prev: block.prev.type ?? false,
            isFirst: loop.first,
            context: context|default(null) ? context : 'basic',
            entry: entry is defined ? entry : null,
            overridableSettings: overridableSettings ?? null
        }) }}
    {% endfor %}
{% endif %}
```

## Pure Twig Equivalent

```twig
{% set matrixPath = 'matrix/' %}
{% if matrix|length %}
    {% set style = style ?? null %}
    {% for block in matrix.all() ?? null %}
        {% include [
            matrixPath ~ 'style/' ~ style ~ '/' ~ block.type,
            matrixPath ~ block.type,
            matrixPath ~ 'default'
        ] with {
            block: block,
            index: loop.index,
            next: block.next.type ?? false,
            prev: block.prev.type ?? false,
            context: context|default(null) ? context : 'basic',
            entry: entry is defined ? entry : null
        } only %}
    {% endfor %}
{% endif %}
```

## Template Path Resolution

The include checks templates in order (first match wins):

1. `matrix/style/{style}/{blockType}` - Style variation
2. `matrix/{blockType}` - Default for type
3. `matrix/default` - Fallback

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- `ctx` context-aware path resolution
- `overridableSettings` support
- Additional hierarchical path options (handle, position, nested)
