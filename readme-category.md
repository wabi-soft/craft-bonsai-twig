# Category Loader - Pure Twig Equivalent

How to replicate the `categoryTemplates()` function using native Twig if you remove the plugin.

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
