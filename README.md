# Bonsai Twig Plugin

Welcome to the **Bonsai Twig Plugin** README! This plugin is designed to streamline your Twig templating experience by providing hierarchical template loading for various element types in Craft CMS 5.

## Features

- **Hierarchical Template Loading**: Automatically resolve templates for entries, categories, items, and matrix blocks with intelligent fallback mechanisms
- **PHP 8.2 & Craft CMS 5 Optimized**: Built with modern PHP features including enums, readonly properties, null-safe operators, and union types
- **Enhanced Debug Tools**: Comprehensive debugging with performance metrics, cache statistics, and template hierarchy visualization
- **Type Safety**: Full type safety with custom exceptions, value objects, and strict type declarations
- **Performance Optimized**: Advanced caching strategies, path deduplication, and performance monitoring
- **Security Enhanced**: Path sanitization, input validation, and protection against traversal attacks

## Requirements

- **PHP**: 8.2.0 or higher
- **Craft CMS**: 5.0.0 or higher

## Usage Guide

### Core Template Functions

The plugin provides four main Twig functions for hierarchical template loading:

#### 1. Entry Templates

```twig
{{ entryTemplates({ entry }) }}
```

**Description**: Loads templates for entry elements with intelligent hierarchy resolution.

**Parameters**:
- `entry` (Entry): The entry element to render
- `path` (string, optional): Custom template path override
- `style` (string, optional): Style variant for the template
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
{# Advanced matrix with full context #}
{% if entry.matrixField|length %}
    {% set style = style ?? null %}
    {% for block in entry.matrixField.all() %}
        {{ matrixTemplates({
            block: block,
            style: style,
            ctx: entry,
            next: block.next.type ?? false,
            prev: block.prev.type ?? false,
            isFirst: loop.first,
            context: context|default('basic'),
            entry: entry,
            variables: { 
                customData: customValue,
                sectionHandle: entry.section.handle
            }
        }) }}
    {% endfor %}
{% endif %}
```

## Debug Features

The plugin provides comprehensive debugging tools that are only active in development mode (`devMode = true`). Debug information is triggered using the `beastmode` URL parameter.

### Debug Modes

#### Universal Debug Parameter

Add `?beastmode` to any URL to enable debug mode for all template types:

```
https://yoursite.test/some-page?beastmode
```

This will show debug information for any Bonsai Twig function calls on that page.

#### Specific Debug Modes

You can specify different levels of debug information:

```
# Show template paths only
?beastmode=path

# Show template hierarchy
?beastmode=hierarchy  

# Show full debug info with performance metrics
?beastmode=full

# Show all available debug information
?beastmode=all
```

#### Template-Specific Debug

Target specific template types for debugging:

```
# Debug only entry templates
?beastmode=entry

# Debug only matrix templates  
?beastmode=matrix

# Debug only category templates
?beastmode=category

# Debug only item templates
?beastmode=item
```

### Debug Information Display

When debug mode is active, you'll see a hover overlay with detailed information:

#### Template Resolution Hierarchy
- **Template Paths**: All attempted template paths in priority order
- **Current Template**: The template that was successfully loaded (highlighted in green)
- **Missing Templates**: Templates that were checked but not found (crossed out)
- **Site-Specific Templates**: Templates with site context (highlighted in blue)

#### Site Context Information
- **Current Site**: The site context for the current request
- **Element Site**: The site associated with the element being rendered
- **Base Site**: The base site for fallback resolution
- **Fallback Site**: Alternative site for template resolution

#### Performance Metrics
- **Resolution Time**: How long it took to resolve the template
- **Memory Delta**: Memory usage change during template resolution
- **Paths Saved**: Number of paths eliminated through optimization
- **Resolution Checkpoints**: Detailed timing for each resolution step

#### Cache Performance
- **Hit Rate**: Percentage of cache hits vs total requests
- **Cache Statistics**: Detailed breakdown by cache type
- **Cache Status**: Whether caching is enabled and why

### Debug Examples

#### Entry Debug Example
```twig
{# Entry template with debug capability #}
{# 
Entry Handler
Add URL parameters to render debug info in devMode:
- Show paths: ?beastmode=path
- Show hierarchy: ?beastmode=hierarchy  
- Show all entry debug: ?beastmode=entry
- Show everything: ?beastmode=all
#}
{{ entryTemplates({ entry }) }}
```

#### Category Debug Example
```twig
{# Category template with debug capability #}
{#
Category Handler  
Add URL parameters to render debug info in devMode:
- Show paths: ?beastmode=path
- Show hierarchy: ?beastmode=hierarchy
- Show all category debug: ?beastmode=category
- Show everything: ?beastmode=all
#}
{{ categoryTemplates({ entry: category }) }}
```

#### Item Debug Example
```twig
{# Item template with debug capability #}
{#
Item Handler
Add URL parameters to render debug info in devMode:
- Show paths: ?beastmode=path
- Show hierarchy: ?beastmode=hierarchy
- Show all item debug: ?beastmode=item  
- Show everything: ?beastmode=all
#}
{{ itemTemplates({ entry: item }) }}
```

#### Matrix Debug Example
```twig
{# Matrix template with debug capability #}
{#
Matrix Handler
Add URL parameters to render debug info in devMode:
- Show paths: ?beastmode=path
- Show hierarchy: ?beastmode=hierarchy
- Show all matrix debug: ?beastmode=matrix
- Show everything: ?beastmode=all
#}
{% for block in entry.matrixField.all() %}
    {{ matrixTemplates({ block: block }) }}
{% endfor %}
```##
 Template Resolution

### How Template Resolution Works

The plugin uses intelligent hierarchical template resolution that checks multiple paths in priority order:

1. **Site-Specific Templates**: Templates in site-specific directories (e.g., `_site1/entry/`)
2. **Type-Specific Templates**: Templates based on element type and handle (e.g., `entry/blog/`)
3. **Style Variants**: Templates with style suffixes (e.g., `entry/blog--featured.twig`)
4. **Fallback Templates**: Generic templates (e.g., `entry/default.twig`)

### Template Path Examples

For an entry with section handle `blog` and type handle `article`:

```
# Checked in this order:
templates/_site1/entry/blog/article.twig
templates/entry/blog/article.twig
templates/_site1/entry/blog/default.twig
templates/entry/blog/default.twig
templates/_site1/entry/default.twig
templates/entry/default.twig
```

With a style parameter:

```twig
{{ entryTemplates({ entry: entry, style: 'featured' }) }}
```

Additional paths are checked:

```
templates/_site1/entry/blog/article--featured.twig
templates/entry/blog/article--featured.twig
templates/_site1/entry/blog/default--featured.twig
templates/entry/blog/default--featured.twig
```

### Matrix Block Resolution

Matrix blocks have enhanced resolution with context awareness:

```
# For a matrix block of type 'textBlock' with style 'hero':
templates/_site1/matrix/textBlock--hero.twig
templates/matrix/textBlock--hero.twig
templates/_site1/matrix/textBlock.twig
templates/matrix/textBlock.twig
templates/_site1/matrix/default--hero.twig
templates/matrix/default--hero.twig
templates/_site1/matrix/default.twig
templates/matrix/default.twig
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

## Performance Features

### Caching

The plugin includes intelligent caching that:
- Caches template path resolution results
- Invalidates cache when templates change
- Provides cache statistics in debug mode
- Can be enabled in development mode via plugin settings

### Optimization

- **Path Deduplication**: Eliminates duplicate template paths before checking
- **Early Exit**: Stops checking once a template is found
- **Batch Operations**: Groups file system operations for efficiency
- **Memory Management**: Uses generators for large template lists

## Security Features

- **Path Sanitization**: Prevents directory traversal attacks
- **Input Validation**: Validates all user-provided parameters
- **Safe Property Access**: Uses null-safe operators throughout
- **Secure Caching**: Uses secure hashing for cache keys

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
?beastmode=entry
?beastmode=full
```

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

### Performance Improvements

The enhanced version provides significant performance improvements:

- **Up to 50% faster** template resolution through path optimization
- **Reduced memory usage** with generator-based path handling
- **Intelligent caching** reduces file system operations
- **Early exit strategies** stop checking once templates are found

### New PHP 8.2 Features

The plugin now leverages modern PHP features:

- **Readonly properties** for immutable configuration
- **Null-safe operators** for safer property access
- **Union types** for flexible parameter handling
- **Enums** for type-safe constants
- **Named parameters** for clearer method calls

## Troubleshooting

### Debug Mode Not Working

1. Ensure `devMode = true` in your Craft configuration
2. Check that you're using the correct URL parameter: `?beastmode`
3. Verify the plugin is installed and enabled

### Templates Not Found

1. Use debug mode to see which paths are being checked: `?beastmode=hierarchy`
2. Verify your template directory structure matches the expected hierarchy
3. Check file permissions on template directories

### Performance Issues

1. Enable caching in plugin settings for development mode testing
2. Use debug mode to check resolution times: `?beastmode=full`
3. Consider simplifying complex template hierarchies

### Cache Issues

1. Clear Craft's template cache: `./craft clear-caches/compiled-templates`
2. Check cache statistics in debug mode: `?beastmode=full`
3. Verify cache permissions in the storage directory

## Support

For issues, feature requests, or questions:

1. Check the debug information using `?beastmode=full`
2. Review the template resolution hierarchy
3. Verify your template structure matches the expected patterns
4. Check Craft and PHP version compatibility

## Changelog

### Version 6.4.0
- Full Craft CMS 5 compatibility
- PHP 8.2 feature utilization
- Enhanced debug tools with performance metrics
- Improved caching strategies
- Security enhancements
- Type safety improvements
- Comprehensive test coverage