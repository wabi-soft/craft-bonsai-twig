# Matrix Loader — `matrixTemplates()`

Full parameter reference, plus how to replicate the function using native Twig if you remove the plugin.

## Parameters

- `block` (required) — the matrix block (or nested entry in Craft 5)
- `style` — style variant; adds `_matrix/style/{style}/{blockType}` to resolution
- `handle` — handle variant; adds `_matrix/handle/{handle}/{blockType}` to resolution
- `ctx` — parent context element; adds `_matrix/ctx/{section}/{type}/…` paths
- `ctxPath` — context path segment (default `ctx`)
- `path` — base path override (default `_matrix`, or `paths.matrix` in config)
- `loopIndex` / `loopLength` — pass `loop.index0` / `loop.length` to expose a Twig-style `loop` variable inside the block template
- `blockIndex`, `nextBlock`, `prevBlock`, `parentBlock` — position-aware resolution
- Any other key (`entry`, `next`, `prev`, `isFirst`, `variables: {…}`, custom data) passes through to the template as a variable; `bonsaiTrace: false` skips LLM trace wrapping for this call and everything it renders

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
            loopIndex: loop.index0,
            loopLength: loop.length,
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
{% set matrixPath = '_matrix/' %}
{% if matrix|length %}
    {% set style = style ?? null %}
    {% set handle = handle ?? null %}
    {% for block in matrix.all() ?? null %}
        {% include [
            handle ? matrixPath ~ 'handle/' ~ handle ~ '/' ~ block.type : '',
            style ? matrixPath ~ 'style/' ~ style ~ '/' ~ block.type : '',
            matrixPath ~ block.type,
            matrixPath ~ 'default'
        ]|filter(v => v != '') with {
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

1. `_matrix/handle/{handle}/{blockType}` - Handle variation (if `handle` provided)
2. `_matrix/style/{style}/{blockType}` - Style variation (if `style` provided)
3. `_matrix/{blockType}` - Default for type
4. `_matrix/default` - Fallback

Context (`ctx`) and position (`blockIndex`) parameters add further paths ahead of the fallback.

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- `ctx` context-aware path resolution
- `overridableSettings` support
- Additional hierarchical path options (handle, position, nested)
