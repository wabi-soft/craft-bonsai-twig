# Product Loader - Pure Twig Equivalent

How to replicate the `productTemplates()` function using native Twig if you remove the plugin.

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{{ productTemplates({ product }) }}
```

## Pure Twig Equivalent

```twig
{% set productPath = 'product/' %}

{% include [
    productPath ~ product.type.handle ~ '/' ~ product.slug,
    productPath ~ product.type.handle ~ '/default',
    productPath ~ product.type.handle,
    productPath ~ 'default'
] ignore missing %}
```

## Template Path Resolution

The include checks templates in order (first match wins):

1. `product/{productType}/{slug}` - Exact product match
2. `product/{productType}/default` - Product type fallback
3. `product/{productType}` - Product type only
4. `product/default` - Global fallback

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- Multi-site `baseSite` prefix support
