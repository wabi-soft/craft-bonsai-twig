# Bonsai Twig Plugin

Welcome to the **Bonsai Twig Plugin** README! This plugin is designed as a **development-only tool** to streamline your Twig templating experience by providing hierarchical template loading for various element types in Craft CMS 5.

## Features

- **Hierarchical Template Loading**: Automatically resolve templates for entries, categories, items, matrix blocks, and assets with intelligent fallback mechanisms
- **PHP 8.2 & Craft CMS 5 Optimized**: Built with modern PHP features including null-safe operators and union types
- **Simple Debug Tools**: Clean, focused debugging that shows template paths and resolution without performance overhead
- **Development-Focused**: Designed specifically for development workflow - no production features or optimizations
- **Enhanced btPath() Function**: Returns complete HTML output with styling, eliminating need for manual Twig wrapping
- **LLM Trace Comments**: Opt-in, dev-only HTML comments mapping rendered DOM back to the winning template, consumable by AI agents reading page source
- **Zero Production Overhead**: Debug features return empty strings in production mode

## Function Reference

Each loader has its own doc with the full parameter list, resolution hierarchy, and a pure-Twig replacement if you ever remove the plugin:

- [`entryTemplates()`](readme-entry.md)
- [`categoryTemplates()`](readme-category.md)
- [`itemTemplates()`](readme-item.md)
- [`matrixTemplates()`](readme-matrix.md)
- [`assetTemplates()`](readme-asset.md)
- [`productTemplates()`](readme-product.md)

Also: [MIGRATION.md](MIGRATION.md) (v8 → v9 upgrade) · [CHANGELOG.md](CHANGELOG.md)

## Requirements

- **PHP**: 8.2.0 or higher
- **Craft CMS**: 5.0.0 or higher

## Getting Started

### 1. Install

The plugin ships inside the project as a Composer path repository:

```jsonc
// composer.json
{
    "repositories": [
        { "type": "path", "url": "_dev/plugins/craft-bonsai-twig" }
    ]
}
```

```bash
ddev composer require wabisoft/craft-bonsai-twig
ddev craft plugin/install bonsai-twig
```

### 2. Add your first template tree

Call a loader where you'd normally write an `{% include %}`:

```twig
{{ entryTemplates({ entry }) }}
```

Then create templates under `templates/_entry/`. Bonsai tries paths from most to least specific and renders the first one that exists:

```
templates/_entry/
├── blog/
│   ├── article/
│   │   └── my-post.twig    ← just this slug
│   ├── article.twig        ← this entry type
│   └── default.twig        ← anything in blog
└── default.twig            ← global fallback
```

Start with a single `_entry/{section}/default.twig` and add more specific templates only where a page needs to differ — no template calls change, resolution picks them up automatically.

The same pattern applies to the other trees: `_matrix/`, `_category/`, `_item/`, `_asset/`, `_product/` — each with its own loader function (see [Usage Guide](#usage-guide)).

### 3. See what's resolving

Add `?beastmode` to any URL (or press **Cmd+B**) to see every path tried and which one won. For AI agents reading page source, enable [LLM trace comments](#llm-trace-comments-v93) instead.

## Template Resolution Strategy (v8.0)

By default, templates resolve **section-first** (`entry/{section}/{type}/...`). In v8.0, you can opt into **type-first** resolution (`entry/{type}/{section}/...`), aligning with Craft 5's standalone entry types.

### Setting the Strategy

Three levels of configuration (highest to lowest precedence):

**1. Per-template (highest priority):**

```twig
{{ entryTemplates({ entry: entry, strategy: 'type' }) }}
{{ itemTemplates({ entry: item, strategy: 'type' }) }}
```

**2. Config file:**

```php
// config/bonsai-twig.php
return [
    'strategy' => 'type', // default is 'section'
];
```

**3. Control Panel:**

Radio buttons in Settings > Bonsai Twig > Template Resolution Strategy.

If unset at all levels, defaults to `'section'` — identical behavior to v7.

### Path Resolution Comparison

For an entry with section `blog` and type `article`:

| Priority | Section-first (default) | Type-first |
|----------|------------------------|------------|
| 1 | `_entry/blog/article/{slug}` | `_entry/article/blog/{slug}` |
| 2 | `_entry/blog/article/_entry` | `_entry/article/blog/_entry` |
| 3 | `_entry/blog/{slug}` | `_entry/article/{slug}` |
| 4 | `_entry/blog/article` | `_entry/article/blog` |
| 5 | `_entry/blog/default` | `_entry/article/default` |
| 6 | `_entry/blog` | `_entry/article` |
| 7 | `_entry/article` | `_entry/blog` |
| 8 | `_entry/default` | `_entry/default` |

Strategies can be mixed — set a global default, then override per template call or per section. Patterns in [readme-entry.md](readme-entry.md) and [readme-item.md](readme-item.md).

### Which Loaders Support Strategy?

| Loader | Strategy support |
|--------|-----------------|
| `entryTemplates()` | Yes |
| `itemTemplates()` | Yes (item + ctx dimensions) |
| `matrixTemplates()` | No (already type-centric) |
| `categoryTemplates()` | No (legacy, no entry types) |
| `assetTemplates()` | No (volume/folder based) |

---

## Usage Guide

Six loader functions, one pattern: pass the element, get the most specific template that exists. Each heading links to the full reference (all parameters, complete hierarchy).

### Entry — [`entryTemplates()`](readme-entry.md)

```twig
{{ entryTemplates({ entry }) }}
{{ entryTemplates({ entry, style: 'featured', strategy: 'type' }) }}
```

### Category — [`categoryTemplates()`](readme-category.md)

```twig
{{ categoryTemplates({ entry: category }) }}
```

### Item — [`itemTemplates()`](readme-item.md)

For related/nested entries; `style` participates in path resolution.

```twig
{% for item in entry.relatedItems.all() %}
    {{ itemTemplates({ entry: item, style: 'compact' }) }}
{% endfor %}
```

### Matrix — [`matrixTemplates()`](readme-matrix.md)

Style-, handle-, context-, and position-aware. Pass `loopIndex`/`loopLength` to expose a `loop` variable inside block templates.

```twig
{% for block in entry.matrixField.all() %}
    {{ matrixTemplates({
        block: block,
        ctx: entry,
        loopIndex: loop.index0,
        loopLength: loop.length,
    }) }}
{% endfor %}
```

### Asset — [`assetTemplates()`](readme-asset.md)

Resolves by volume → folder → filename.

```twig
{{ assetTemplates({ asset: image }) }}
```

### Product — [`productTemplates()`](readme-product.md)

```twig
{{ productTemplates({ product }) }}
```

### Template Path Display — `btPath()`

Returns a self-styled HTML block listing every attempted path with the resolved one marked ✓. Call it anywhere in a template — no wrapping needed; returns an empty string in production.

```twig
{{ btPath() }}

{# Or inside an HTML comment for minimal visual impact #}
<!-- {{ btPath() }} -->
```

## Debug Features

The plugin provides debugging tools that are only active in development mode (`devMode = true`).

### Debug Mode

#### Keyboard Shortcut (Recommended)

Press **Cmd+B** (Mac) or **Ctrl+B** (Windows/Linux) to open the Beastmode options modal. This lets you select which template types to debug without manually editing the URL.

#### URL Parameter

Add `?beastmode` to any URL to enable debug mode for all template types:

```
https://yoursite.test/some-page?beastmode
```

Or filter by specific types (comma-separated):

```
https://yoursite.test/some-page?beastmode=entry,matrix
```

Valid types: `entry`, `category`, `item`, `matrix`, `asset`, `product`, or `all`

### Debug Information Display

When debug mode is active, you'll see clean debug output showing:

#### Template Resolution Information

- **Template Paths**: All attempted template paths in priority order
- **Resolved Template**: The template that was successfully loaded (marked with ✓)
- **Template Type**: Context information (Entry, Matrix, Category, Item)

The debug output focuses on essential information without performance metrics or complex styling.

Every loader call gains the overlay automatically — no template changes needed.

### LLM Trace Comments (v9.3)

When enabled, every Bonsai-resolved render is bracketed in machine-parseable HTML comments mapping the rendered DOM back to the winning template and its resolution context — the same map the beastmode overlay shows, but inline in the page source, consumable by an LLM/agent:

```html
<!-- bonsai:start id="c4f9-3" tpl="_entry/blog/default" type="entry" el="blogPost#64" section="blog" tried="_entry/blog/blogPost/my-post|_entry/blog/blogPost/_entry|_entry/blog/my-post|_entry/blog/blogPost" -->
…rendered template output…
<!-- bonsai:end id="c4f9-3" -->
```

Pairs match on `id` (a per-page nonce + counter, so page content can't forge plausible pairs); nested loader calls yield nested pairs, so the comment tree mirrors the render tree. A page-level `<!-- bonsai:trace v="1" nonce="…" -->` marker is emitted whenever tracing is active — even on pages with no Bonsai renders. Comments carry paths, ids, and handles only — never field values.

The full attribute grammar lives in [example.CLAUDE.md](example.CLAUDE.md) — the one file consuming agents read.

#### Enabling

Two independent conditions, both required:

```
emit == devMode === true AND llmMode === true
```

`llmMode` is the switch; `devMode` is the safety floor — trace comments **never** render in production, even with `BONSAI_LLM_MODE=true` in a production `.env`.

> **Staging caveat:** `tried` enumerates your template tree and content-model handles in every page's source. Don't enable `llmMode` on internet-reachable staging servers that run `devMode=true`.

Enable via any of (precedence: env > config file > CP setting):

```php
// config/bonsai-twig.php
return [
    'llmMode' => true,
];
```

```bash
# .env
BONSAI_LLM_MODE=true
```

Or toggle "LLM trace comments" in the plugin's CP settings.

#### Opting out (non-HTML contexts)

A wrapped template rendering into JSON-LD, `<script>`, `<style>`, `<title>`, an attribute value, or whitespace-sensitive output (`<pre>`) would be corrupted by an HTML comment:

```twig
{# Suppress wrapping for this render and everything it renders #}
{{ entryTemplates({ entry, bonsaiTrace: false }) }}
```

#### Labelling static components

Trace comments mark dynamic resolutions only — plain `{% include %}`s are followed by reading source. For components included through *dynamic* names (or just for convenience), templates can self-label using `bonsaiTraceEnabled()`, which gates on the same devMode + llmMode switch:

```twig
{# Inline, inside any component #}
{% if bonsaiTraceEnabled() %}<!-- cmp: {{ _self }} -->{% endif %}
```

```twig
{# Or as a shared macro, e.g. templates/_macros/dev.twig. This form calls the
   plugin instance instead of the Twig function, so templates keep compiling
   if the plugin is ever removed (an undefined Twig function is a
   compile-time error). Note: _self inside a macro names the macro's own
   file, so the caller must pass it. #}
{% macro trace(tpl) -%}
    {%- set bonsai = craft.app.plugins.getPlugin('bonsai-twig') -%}
    {%- if bonsai and bonsai.traceEnabled() %}<!-- cmp: {{ tpl }} -->{% endif -%}
{%- endmacro %}

{# In a component: #}
{% import '_macros/dev' as dev %}
{{ dev.trace(_self) }}
```

#### Agent consumption recipe

[example.CLAUDE.md](example.CLAUDE.md) is a ready-to-append snippet for a consumer project's CLAUDE.md — the comment grammar, how to fetch raw source, nesting semantics, and how to read a fallthrough:

```bash
cat vendor/wabisoft/craft-bonsai-twig/example.CLAUDE.md >> CLAUDE.md
```

## Integration with Craft 5

### Unified Element Model

In Craft CMS 5, categories are now entries, which simplifies template handling. The plugin automatically handles this unification while maintaining backward compatibility.

### Integration with Craft 5 `render()`

The plugin works alongside Craft 5's built-in `render()` method. While `render()` looks for templates in `_partials/{elementType}/{elementName}.twig`, Bonsai Twig provides more sophisticated hierarchical resolution.

**Craft 5 render():**

```twig
{{ entry.render() }}  {# Looks for _partials/entry/blog.twig #}
```

**Bonsai Twig:**

```twig
{{ entryTemplates({ entry }) }}  {# Checks multiple hierarchical paths #}
```

You can use both approaches as needed - `render()` for simple cases and Bonsai Twig for complex hierarchical template systems.

## Development-Only Focus

This plugin is designed specifically as a development tool and includes:

### Simplified Architecture

- **No Caching**: Templates change frequently in development, so no caching overhead
- **Direct File System Checks**: Simple template existence checking without optimization layers
- **Minimal Dependencies**: Only essential services for template loading
- **Straightforward Logic**: Easy to understand and maintain codebase

### Basic Security

- **Path Sanitization**: Basic path cleaning to prevent directory traversal
- **Input Validation**: Simple parameter type checking
- **Safe Property Access**: Uses null-safe operators for element properties

## Migration

Upgrading from v8? The v9 breaking changes (underscore path prefixes, plugin handle, config file rename) are covered in [MIGRATION.md](MIGRATION.md).

## Troubleshooting

### Debug Mode Not Working

1. Ensure `devMode = true` in your Craft configuration
2. Check that you're using the correct URL parameter: `?beastmode`
3. Verify the plugin is installed and enabled

### Templates Not Found

1. Use debug mode to see which paths are being checked: `?beastmode`
2. Verify your template directory structure matches the expected hierarchy
3. Check file permissions on template directories

### Template Resolution Issues

1. Use debug mode to see which paths are being checked: `?beastmode` or `?beastmode=entry,matrix`
2. Use the enhanced `btPath()` function in your templates to see resolution info
3. Consider simplifying complex template hierarchies

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
