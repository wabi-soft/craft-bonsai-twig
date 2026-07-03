# Category Loader — `categoryTemplates()`

Full parameter reference, plus how to replicate the function using native Twig if you remove the plugin.

## Parameters

- `entry` (required) — the category element (in Craft 5, categories are entries)
- `path` — base path override (default `_category`, or `paths.category` in config)
- `baseSite` — site handle to prefix paths for multi-site template trees
- Any other key (`style`, custom data) passes through to the template as a variable; `bonsaiTrace: false` skips LLM trace wrapping for this call and everything it renders

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{{ categoryTemplates({ entry: category }) }}
```

## Pure Twig Equivalent

```twig
{% set path = '_category/' %}

{% include [
    path ~ category.group.handle ~ '/' ~ category.slug,
    path ~ category.group.handle ~ '/default',
    path ~ category.group.handle,
    path ~ 'default'
] ignore missing %}
```

## Template Path Resolution

The include checks templates in order (first match wins):

1. `_category/{group}/{slug}` - Exact category match
2. `_category/{group}/default` - Group fallback
3. `_category/{group}` - Group only
4. `_category/default` - Global fallback

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- Multi-site `baseSite` prefix support
