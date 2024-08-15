# Twig Template Code Helper Plugin

Welcome to the **Twig Template Code Helper Plugin** README! This plugin is designed to streamline your Twig templating experience by loading code based on specific element types in Craft CMS 5.

## Features

- **Automatic Template Loading**: Load templates for various element types such as entries, categories, assets, users, and matrix rows.
- **Parameter Support**: Optional parameters for enhanced debugging during development.

## Usage Guide

### Core Template Functions

1. **Item Templates**
   - **Function**: `itemTemplates`
   - **Usage**: `{{ itemTemplates({ entry }) }}`
   - **Description**: Loops through nested entries.
   - **Suggested Comment**:
```  
{#
Item Handler
Append URL paramters to the URL to render debug info while in devMode
|- one: ?showItemPath=true
|- two: ?showItemHierarchy=true
|- both: ?showItemPath=true&showItemHierarchy=true
#}
```
2. **Entry Templates**
   - **Function**: `entryTemplates`
   - **Usage**: `{{ entryTemplates({ entry }) }}`
   - **Description**: Loads templates for core entry elements.
   - **Suggested Comments**:
```     
{#
Entry Handler
 Append URL paramters to the URL to render debug info while in devMode
 |- use one: ?showEntryPath=true
 |- the other: ?showEntryHierarchy=true
 |- or both: ?showEntryPath=true&showEntryHierarchy=true
#}
```

3. **Category Templates**
   - **Function**: `categoryTemplates`
   - **Usage**: `{{ categoryTemplates({ entry }) }}`
   - **Description**: Loads templates for category elements.
   - **Suggested Comments**:
```
{#
Category Handler
Append URL paramters to the URL to render debug info while in devMode
|- one: ?showCategoryPath=true
|- two: ?showCategoryHierarchy=true
|- both: ?showCategoryPath=true&showCategoryHierarchy=true
#}
```

4. **Matrix Templates**
   - **Function**: `matrixTemplates`
   - **Usage**: `{{ matrixTemplates({ matrix }) }}`
   - **Description**: Loads templates for matrix rows.
   - **Suggested Commment**:
  ```
{#
Matrix Handler
Append URL paramters to the URL to render debug info while in devMode
|- one: showMatrixPath=true&showMatrixHierarchy=true
|- two: showMatrixHierarchy=true
|- both: showMatrixPath=true&showMatrixHierarchy=true
#}
```
**Advanced Matrix** 
Additionally, you can set this up to handle additional parameters. The built-in on is "style" to easily change the visual of the block. To do so, you can feed the blocks in individually like this 
```
{% if matrix|length %}

    {% set style = style ?? null %}
    {% for block in matrix.collect() ?? null %}
        {{ matrixTemplates({
            block: block,
            style: style,
            ctx: entry ?? null,
            next: block.next.type ?? false,
            prev: block.prev.type ?? false,
            isFirst: loop.first,
            context: context|default(null) ? context : 'basic',
            entry: entry is defined ? entry : null,
            overridableSettings: overridableSettings ?? null
        }) }}
    {% endfor %}
{% endif %}
```
rather thank passing the entire "matrix" group in.

### Parameters

Enhance your debugging with the following optional parameters in **devMode**:

- **`showPathParam`**
  - **Usage**: `{{ entryTemplates({ entry, showPathParam: true }) }}`
  - **Description**: Displays the path of the template being rendered.

- **`showHierarchyParam`**
  - **Usage**: `{{ entryTemplates({ entry, showHierarchyParam: true }) }}`
  - **Description**: Shows the hierarchy of the template being rendered.

### Notes on Craft CMS 5

In Craft CMS 5, categories and matrix elements are now treated as entries and follow the entry model. This unification simplifies template handling and ensures consistency across different element types.


## Integration with Craft 5 `render()`

Craft 5 introduces a convenient `render()` method for elements, enabling the rendering of elements directly within templates. By default, the `render()` function looks for templates in the `_partials` directory, using the element’s type and name.

### Default Template Path

- **Path Structure**: `_partials/{elementType}/{elementName}.twig`
- **Example**: For an asset in a volume with the handle `images`, the path would be `_partials/asset/images.twig`.

### Using `render()` for Nested Matrix Blocks

You can utilize `render()` to manage nested matrix blocks effectively:

```twig
{{ element.render() }}

## Example Usages

### Basic Usage

```twig
{{ itemTemplates({ entry }) }}

```twig
{{ matrixTemplates({ matrix }) }}

### Advanced Usage
Append URL paramters to the URL to render debug info while in devMode

?showPathParam=true&showHierarchyParam=true
