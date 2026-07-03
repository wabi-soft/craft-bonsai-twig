# Asset Loader — `assetTemplates()`

Full parameter reference, plus how to replicate the function using native Twig if you remove the plugin.

## Parameters

- `asset` (required) — the asset element
- `path` — base path override (default `_asset`, or `paths.asset` in config)
- `baseSite` — site handle to prefix paths for multi-site template trees
- Any other key passes through to the template as a variable; `bonsaiTrace: false` skips LLM trace wrapping for this call and everything it renders

## Example Template Structure

For an asset with volume `images`, folder `products/featured`, filename `hero.jpg`:

```
templates/_asset/
  images/
    products/
      featured/
        hero.twig       ← matches this file
        default.twig    ← folder fallback
    default.twig        ← volume fallback
  default.twig          ← global fallback
```

## With Plugin

```twig
{# Debug: add ?beastmode to URL, then Cmd+B / Ctrl+B for overlay #}

{{ assetTemplates({ asset }) }}
```

## Pure Twig Equivalent

```twig
{% set assetPath = '_asset/' %}
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

1. `_asset/{volume}/{folder}/{filename}` - Exact asset match (with folder)
2. `_asset/{volume}/{filename}` - Exact asset match (no folder)
3. `_asset/{volume}/{folder}/default` - Folder fallback
4. `_asset/{volume}/default` - Volume fallback
5. `_asset/{volume}` - Volume only
6. `_asset/default` - Global fallback

## What You Lose

- Debug overlay (Cmd+B / Ctrl+B) with `?beastmode` parameter
- Visual template path resolution display
- Multi-site `baseSite` prefix support
