# Asset Loader - Pure Twig Equivalent

How to replicate the `assetTemplates()` function using native Twig if you remove the plugin.

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{{ assetTemplates({ asset }) }}
```

## Pure Twig Equivalent

```twig
{% set assetPath = 'asset/' %}
{% set volume = asset.volume.handle %}
{% set folder = asset.folder.path|trim('/') %}
{% set filename = asset.filename|split('.')|first %}

{% include [
    folder ? assetPath ~ volume ~ '/' ~ folder ~ '/' ~ filename : assetPath ~ volume ~ '/' ~ filename,
    folder ? assetPath ~ volume ~ '/' ~ folder ~ '/default' : '',
    assetPath ~ volume ~ '/default',
    assetPath ~ volume,
    assetPath ~ 'default'
]|filter(v => v != '') ignore missing %}
```

## Template Path Resolution

The include checks templates in order (first match wins):

1. `asset/{volume}/{folder}/{filename}` - Exact asset match (with folder)
2. `asset/{volume}/{filename}` - Exact asset match (no folder)
3. `asset/{volume}/{folder}/default` - Folder fallback
4. `asset/{volume}/default` - Volume fallback
5. `asset/{volume}` - Volume only
6. `asset/default` - Global fallback

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- Multi-site `baseSite` prefix support
