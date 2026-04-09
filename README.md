# Bonsai Twig Plugin

Welcome to the **Bonsai Twig Plugin** README! This plugin is designed as a **development-only tool** to streamline your Twig templating experience by providing hierarchical template loading for various element types in Craft CMS 5.

## Features

- **Hierarchical Template Loading**: Automatically resolve templates for entries, categories, items, matrix blocks, and assets with intelligent fallback mechanisms
- **PHP 8.2 & Craft CMS 5 Optimized**: Built with modern PHP features including null-safe operators and union types
- **Simple Debug Tools**: Clean, focused debugging that shows template paths and resolution without performance overhead
- **Development-Focused**: Designed specifically for development workflow - no production features or optimizations
- **Enhanced btPath() Function**: Returns complete HTML output with styling, eliminating need for manual Twig wrapping
- **Zero Production Overhead**: Debug features return empty strings in production mode

## Pure Twig Equivalents

If you need to remove the plugin, see these guides for native Twig replacements:

- [Entry Templates](readme-entry.md) - `entryTemplates()`
- [Category Templates](readme-category.md) - `categoryTemplates()`
- [Item Templates](readme-item.md) - `itemTemplates()`
- [Matrix Templates](readme-matrix.md) - `matrixTemplates()`
- [Asset Templates](readme-asset.md) - `assetTemplates()`
- [Product Templates](readme-product.md) - `productTemplates()`

## Requirements

- **PHP**: 8.2.0 or higher
- **Craft CMS**: 5.0.0 or higher

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
    'strategy' => 'type',
];
```

**3. Control Panel:**

Radio buttons in Settings > Bonsai Twig > Template Resolution Strategy.

If unset at all levels, defaults to `'section'` — identical behavior to v7.

### Path Resolution Comparison

For an entry with section `blog` and type `article`:

| Priority | Section-first (default) | Type-first |
|----------|------------------------|------------|
| 1 | `entry/blog/article/{slug}` | `entry/article/blog/{slug}` |
| 2 | `entry/blog/article/_entry` | `entry/article/blog/_entry` |
| 3 | `entry/blog/{slug}` | `entry/article/{slug}` |
| 4 | `entry/blog/article` | `entry/article/blog` |
| 5 | `entry/blog/default` | `entry/article/default` |
| 6 | `entry/blog` | `entry/article` |
| 7 | `entry/article` | `entry/blog` |
| 8 | `entry/default` | `entry/default` |

### Mixed Strategies

You can use both strategies in the same project. Set a global default via config, then override per-template:

```twig
{# Most templates use global strategy (e.g., 'type') #}
{{ entryTemplates({ entry: entry }) }}

{# This one overrides to section-first #}
{{ entryTemplates({ entry: entry, strategy: 'section' }) }}
```

#### Per-Section Strategy

Use a conditional to pick the strategy based on the current section:

```twig
{# Sections with shared entry types use type-first; the rest use section-first #}
{% set strategy = entry.section.handle in ['blog', 'resources'] ? 'type' : 'section' %}
{{ entryTemplates({ entry: entry, strategy: strategy }) }}
```

The same pattern works with `itemTemplates()`:

```twig
{% set strategy = item.section.handle in ['blog', 'resources'] ? 'type' : 'section' %}
{{ itemTemplates({ entry: item, strategy: strategy }) }}
```

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

### Core Template Functions

The plugin provides five main Twig functions for hierarchical template loading:

#### 1. Entry Templates

```twig
{{ entryTemplates({ entry }) }}
```

**Description**: Loads templates for entry elements with intelligent hierarchy resolution.

**Parameters**:

- `entry` (Entry): The entry element to render
- `path` (string, optional): Custom template path override
- `strategy` (string, optional): `'section'` (default) or `'type'` for type-first resolution
- `style` (string, optional): Style variant (forwarded to the template; does not alter Entry path resolution)
- `context` (Element, optional): Additional context element
- `baseSite` (string, optional): Base site handle for multi-site setups
- `variables` (array, optional): Additional variables to pass to the template
  **Example**:

```twig
{# Basic usage #}
{{ entryTemplates({ entry }) }}

{# With custom path and style #}
{{ entryTemplates({
    entry: entry,
    path: 'custom/path',
    style: 'featured'
}) }}

{# With type-first strategy #}
{{ entryTemplates({
    entry: entry,
    strategy: 'type'
}) }}

{# With additional context #}
{{ entryTemplates({
    entry: entry,
    context: parentEntry,
    variables: { customVar: 'value' }
}) }}
```

#### 2. Category Templates

```twig
{{ categoryTemplates({ entry }) }}
```

**Description**: Loads templates for category elements (in Craft 5, categories are entries).

**Parameters**: Same as `entryTemplates`

**Example**:

```twig
{# Basic category rendering #}
{{ categoryTemplates({ entry: category }) }}

{# Category with style variant #}
{{ categoryTemplates({
    entry: category,
    style: 'card'
}) }}
```

#### 3. Item Templates

```twig
{{ itemTemplates({ entry }) }}
```

**Description**: Specialized template loading for nested entry relationships and complex hierarchies.

**Parameters**: Same as `entryTemplates`

**Example**:

```twig
{# Render related items #}
{% for item in entry.relatedItems.all() %}
    {{ itemTemplates({ entry: item }) }}
{% endfor %}

{# Item with context awareness #}
{{ itemTemplates({
    entry: item,
    context: parentEntry,
    style: 'compact'
}) }}

{# Item with type-first strategy #}
{{ itemTemplates({
    entry: item,
    strategy: 'type'
}) }}
```

#### 4. Matrix Templates

```twig
{{ matrixTemplates({ block }) }}
```

**Description**: Advanced template loading for Matrix blocks with style and context awareness.

**Parameters**:

- `block` (MatrixBlock): The matrix block to render
- `style` (string, optional): Style variant for the block
- `ctx` or `context` (Element, optional): Parent context element
- `loopIndex` (int, optional): Current loop iteration (0-indexed) for Twig loop variable
- `loopLength` (int, optional): Total number of items in loop for Twig loop variable
- `variables` (array, optional): Additional variables to pass to the template
- `next` (string, optional): Next block type for navigation
- `prev` (string, optional): Previous block type for navigation
- `isFirst` (bool, optional): Whether this is the first block
- `entry` (Entry, optional): Parent entry element
- `variables` (array, optional): Additional template variables

**Basic Example**:

```twig
{# Simple matrix block rendering #}
{% for block in entry.matrixField.all() %}
    {{ matrixTemplates({ block: block }) }}
{% endfor %}
```

**Advanced Example**:

```twig
{# Advanced matrix with full context and loop variables #}
{% if entry.matrixField|length %}
    {% set style = style ?? null %}
    {% for block in entry.matrixField.all() %}
        {{ matrixTemplates({
            block: block,
            style: style,
            loopIndex: loop.index0,    {# Pass current iteration (0-indexed) #}
            loopLength: loop.length,   {# Pass total number of blocks #}
            ctx: entry,
            next: block.next.type ?? false,
            prev: block.prev.type ?? false,
            isFirst: loop.first,
            context: context|default('basic'),
            entry: entry,
            variables: {
                customData: customValue,
                sectionHandle: entry.section.handle,
                blockPosition: loop.index,
                totalBlocks: loop.length
            }
        }) }}
    {% endfor %}
{% endif %}
```

**Variables Parameter**:

You can pass additional variables using either approach:

```twig
{# Approach 1: Direct parameters (backward compatible) #}
{{ matrixTemplates({
    block: block,
    customData: 'some value',
    sectionHandle: entry.section.handle
}) }}

{# Approach 2: Using variables parameter (recommended) #}
{{ matrixTemplates({
    block: block,
    loopIndex: loop.index0,
    loopLength: loop.length,
    variables: {
        customData: 'some value',
        sectionHandle: entry.section.handle,
        blockPosition: loop.index
    }
}) }}
```

Both approaches make the variables available in your matrix template. The `variables` parameter is useful for organizing custom data separately from system parameters.

#### 5. Asset Templates

```twig
{{ assetTemplates({ asset }) }}
```

**Description**: Loads templates for Craft asset elements with intelligent hierarchy based on volume, folder, and filename.

**Parameters**:

- `asset` (Asset): The asset element to render
- `path` (string, optional): Custom template path override (defaults to 'asset')
- `baseSite` (string, optional): Base site handle for multi-site setups

**Template Hierarchy**:

The asset loader checks for templates in this order:

1. `asset/{volume}/{folder}/{filename}` - Most specific match for a particular file
2. `asset/{volume}/{filename}` - For assets in root of volume (no folder)
3. `asset/{volume}/{folder}/default` - Default template for a specific folder
4. `asset/{volume}/default` - Default template for the volume
5. `asset/{volume}` - Volume-level template
6. `asset/default` - Global fallback

**Example Usage**:

```twig
{# Basic asset rendering #}
{{ assetTemplates({ asset: image }) }}

{# Render images in a gallery #}
{% for image in entry.gallery.all() %}
    {{ assetTemplates({ asset: image }) }}
{% endfor %}

{# Custom path #}
{{ assetTemplates({
    asset: document,
    path: 'downloads'
}) }}

{# Multi-site support #}
{{ assetTemplates({
    asset: image,
    baseSite: 'fr'
}) }}
```

**Example Template Structure**:

For an asset with volume `images`, folder `products/featured`, filename `hero.jpg`:

```
templates/
  asset/
    images/
      products/
        featured/
          hero.twig           ← Matches specific file
          default.twig        ← Folder fallback
      default.twig            ← Volume fallback
    default.twig              ← Global fallback
```

**Use Cases**:

- Custom rendering for different asset types (images, videos, PDFs)
- Volume-specific templates (e.g., different rendering for "documents" vs "images")
- Folder-based organization (e.g., "products" vs "blog" assets)
- File-specific templates for important assets

### 6. Enhanced Template Path Display (`btPath()`)

The `btPath()` function has been enhanced to provide complete HTML output with styling, eliminating the need for manual Twig wrapping. This is particularly useful for item and matrix templates where you need to quickly identify which template is being used.

**Key Features:**
- **Complete HTML Output**: Returns formatted HTML with styling instead of plain text
- **Automatic Context Detection**: Shows appropriate template type (Matrix, Entry, Category, Item)
- **Zero Production Overhead**: Returns empty string in production mode
- **No Manual Wrapping**: No need for conditional blocks or manual HTML

#### Enhanced Usage Examples

##### Simple Usage (New - Recommended)
Just call the function directly - it returns complete HTML:

```twig
{{ btPath() }}
```

This automatically outputs styled HTML like:
```html
<div class="bt-debug-output" id="bt-debug-abc123">
<style>
#bt-debug-abc123 {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  font-size: 12px;
  background: #1e1e1e;
  color: #d4d4d4;
  border: 1px solid #454545;
  border-radius: 6px;
  padding: 12px;
  margin: 8px 0;
}
/* Additional scoped styles... */
</style>
<div class="bt-debug-header">Matrix Block Template</div>
<ul class="bt-debug-list">
  <li class="bt-debug-item bt-debug-item--missing">→ matrix/textBlock--hero.twig</li>
  <li class="bt-debug-item bt-debug-item--resolved">✓ matrix/textBlock.twig</li>
  <li class="bt-debug-item bt-debug-item--missing">→ matrix/default.twig</li>
</ul>
</div>
```

The output includes:
- A unique `id` attribute to scope CSS and avoid conflicts
- Inline `<style>` block with scoped selectors
- Dark theme styling for developer-friendly display
- Visual indicators: ✓ for resolved template, → for attempts

##### Legacy Usage (Manual Wrapping)
You can still manually wrap the output if needed, though it's no longer necessary:

```twig
{% if btPath() %}
    <div class="custom-wrapper">
        {{ btPath() }}
    </div>
{% endif %}
```

**Note**: The legacy `<pre class="bonsai-debug">` output format from earlier versions is no longer used. The current implementation uses the `<div class="bt-debug-output">` wrapper with inline styles shown above.

##### HTML Comment Usage
For minimal visual impact:

```twig
<!-- {{ btPath() }} -->
```

#### Output Features

The enhanced `btPath()` automatically includes:

- **Template Type Context**: Shows "Matrix Block Template", "Entry Template", etc.
- **Resolved Template Marking**: The found template is marked with ✓
- **Clean Styling**: Simple, unobtrusive CSS styling
- **Attempted Paths**: All paths checked during resolution

#### Production Mode Behavior

In production mode (`devMode = false`), `btPath()` returns an empty string with zero overhead:

```twig
{{ btPath() }}  <!-- Returns empty string in production -->
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

### Debug Examples

#### Entry Debug Example

```twig
{# Entry template with debug capability #}
{# Add ?beastmode to URL to see template resolution info #}
{{ entryTemplates({ entry }) }}
```

#### Category Debug Example

```twig
{# Category template with debug capability #}
{# Add ?beastmode to URL to see template resolution info #}
{{ categoryTemplates({ entry: category }) }}
```

#### Item Debug Example

```twig
{# Item template with debug capability #}
{# Add ?beastmode to URL to see template resolution info #}
{{ itemTemplates({ entry: item }) }}
```

#### Matrix Debug Example

```twig
{# Matrix template with debug capability #}
{# Add ?beastmode to URL to see template resolution info #}
{% for block in entry.matrixField.all() %}
    {{ matrixTemplates({ block: block }) }}
{% endfor %}
```

#### Enhanced btPath() Debug Example

```twig
{# Enhanced btPath() - returns complete HTML output #}
{{ btPath() }}

{# Or use in HTML comments for minimal impact #}
<!-- {{ btPath() }} -->
```

### Suggested Minimum Usage

For the most common use cases, here are the recommended minimal implementations:

#### Basic Matrix Templates (Craft 4→5 Migration)

```twig
{# Replaces the old matrix include pattern #}
{% for block in matrix.all() %}
    {{ matrixTemplates({
        block: block,
        handle: handle ?? null,
        style: style ?? null
    }) }}
{% endfor %}
```

This automatically resolves templates in this order:

1. `matrix/handle/{handle}/{blockType}.twig` (if handle provided)
2. `matrix/style/{style}/{blockType}.twig` (if style provided)
3. `matrix/{blockType}.twig` (main template)
4. `matrix/default.twig` (fallback)

#### Simple Entry Templates

```twig
{# Basic entry rendering #}
{{ entryTemplates({ entry: entry }) }}

{# Entry with style variant #}
{{ entryTemplates({ entry: entry, style: 'featured' }) }}
```

#### Basic Category Templates

```twig
{# Simple category rendering #}
{{ categoryTemplates({ entry: category }) }}
```

#### Basic Item Templates

```twig
{# For related entries or nested content #}
{% for item in entry.relatedItems.all() %}
    {{ itemTemplates({ entry: item }) }}
{% endfor %}
```

Template Resolution

### How Template Resolution Works

The plugin uses intelligent hierarchical template resolution that checks multiple paths in priority order:

1. **Type-Specific Templates**: Templates based on element type and handle (e.g., `entry/blog/article`)
2. **Style Variants**: Style-based paths for items and matrix blocks (e.g., `item/{section}/{style}`, `matrix/style/{style}/{blockType}`)
3. **Fallback Templates**: Generic templates (e.g., `entry/default.twig`)

### Template Path Examples

For an entry with section handle `blog` and type handle `article`:

```
# Checked in this order:
_entry/blog/article/{slug}
_entry/blog/article/_entry
_entry/blog/{slug}
_entry/blog/article
_entry/blog/default
_entry/blog
_entry/article
_entry/default
```

Note: The `style` parameter is passed to the template as a variable but does not change the entry path resolution. For style-aware path resolution, use `itemTemplates()` or `matrixTemplates()` instead.

### Matrix Block Resolution

Matrix blocks have style-aware path resolution:

```
# For a matrix block of type 'textBlock' with style 'hero':
_matrix/style/hero/textBlock
_matrix/textBlock
_matrix/default
```

Additional context-aware paths are available when using `ctx`, `handle`, or position parameters.

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

## Migration Guide

### Upgrading from Previous Versions

The plugin maintains full backward compatibility, but you can take advantage of new features:

#### Enhanced Debug Parameters

**Old way:**

```
?showEntryPath=true&showEntryHierarchy=true
```

**New way (recommended):**

```
?beastmode
?beastmode=entry
?beastmode=entry,matrix
```

Or use the keyboard shortcut: **Cmd+B** / **Ctrl+B**

#### Template Function Signatures

All existing function signatures remain unchanged:

```twig
{# These continue to work exactly as before #}
{{ entryTemplates({ entry }) }}
{{ categoryTemplates({ entry }) }}
{{ itemTemplates({ entry }) }}
{{ matrixTemplates({ block }) }}
```

#### New Optional Parameters

You can now use additional parameters for enhanced functionality:

```twig
{# New optional parameters #}
{{ entryTemplates({
    entry: entry,
    style: 'featured',        # New: style variants
    context: parentEntry,     # New: context awareness
    variables: { key: value } # New: additional variables
}) }}
```

### Simplified Implementation

The simplified version focuses on reliability and maintainability:

- **Straightforward Logic**: Simple template resolution without complex optimization
- **Reduced Complexity**: Fewer moving parts means fewer potential issues
- **Enhanced Debug Experience**: Improved btPath() function with complete HTML output
- **Zero Production Overhead**: Debug features automatically disabled in production

### PHP 8.2 Features

The plugin leverages essential modern PHP features:

- **Null-safe operators** for safer property access
- **Union types** for flexible parameter handling
- **Enums** for type-safe constants

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

## Support

For issues, feature requests, or questions:

1. Check the debug information using `?beastmode`
2. Use `{{ btPath() }}` in your templates to see resolution hierarchy
3. Verify your template structure matches the expected patterns
4. Check Craft and PHP version compatibility

## Changelog

### Version 8.0.0

- **Type-first template resolution strategy** — opt-in `strategy: 'type'` parameter for EntryLoader and ItemLoader
- Three-level configuration: per-template, config file, or CP settings
- Strategy displayed in beastmode overlay and `btPath()` debug output
- Version bump to 8.0.0 (no breaking changes when strategy is unset)

### Version 6.4.0

- Full Craft CMS 5 compatibility
- Simplified architecture focused on development workflow
- Enhanced btPath() function with complete HTML output
- Removed performance monitoring and caching complexity
- Streamlined debug tools for essential information only
- Development-only tool focus with zero production overhead
