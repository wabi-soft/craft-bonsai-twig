# Category Loader - Pure Twig Equivalent

How to replicate the `categoryTemplates()` function using native Twig if you remove the plugin.

## With Plugin

```twig
{#
While in devMode:

To see full path: ?showCategoryHierarchy=1
To see applied template: ?showCategoryPath=1
#}

{{ categoryTemplates({ entry: category }) }}
```

## Pure Twig Equivalent

```twig
{% set path = 'category/' %}

{% include [
    path ~ category.group.handle ~ '/' ~ category.slug,
    path ~ category.group.handle ~ '/default',
    path ~ category.group.handle,
    path ~ 'default'
] ignore missing %}
```

## Template Path Resolution

The include checks templates in order (first match wins):

1. `category/{group}/{slug}` - Exact category match
2. `category/{group}/default` - Group fallback
3. `category/{group}` - Group only
4. `category/default` - Global fallback

## What You Lose

- Debug output (`?showCategoryHierarchy=1`, `?showCategoryPath=1`)
- Multi-site `baseSite` prefix support
