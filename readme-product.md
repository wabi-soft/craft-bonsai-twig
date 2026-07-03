# Product Loader — `productTemplates()`

Full parameter reference, plus how to replicate the function using native Twig if you remove the plugin.

## Parameters

- `product` (required) — the Commerce product element
- `path` — base path override (default `_product`, or `paths.product` in config)
- `baseSite` — site handle to prefix paths for multi-site template trees
- Any other key passes through to the template as a variable; `bonsaiTrace: false` skips LLM trace wrapping for this call

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{{ productTemplates({ product }) }}
```

## Pure Twig Equivalent

```twig
{% set productPath = '_product/' %}

{% include [
    productPath ~ product.type.handle ~ '/' ~ product.slug,
    productPath ~ product.type.handle ~ '/default',
    productPath ~ product.type.handle,
    productPath ~ 'default'
] ignore missing %}
```

## Template Path Resolution

The include checks templates in order (first match wins):

1. `_product/{productType}/{slug}` - Exact product match
2. `_product/{productType}/default` - Product type fallback
3. `_product/{productType}` - Product type only
4. `_product/default` - Global fallback

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- Multi-site `baseSite` prefix support
