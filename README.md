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

2. **Entry Templates**
   - **Function**: `entryTemplates`
   - **Usage**: `{{ entryTemplates({ entry }) }}`
   - **Description**: Loads templates for core entry elements.

3. **Category Templates**
   - **Function**: `categoryTemplates`
   - **Usage**: `{{ categoryTemplates({ entry }) }}`
   - **Description**: Loads templates for category elements.

4. **Matrix Templates**
   - **Function**: `matrixTemplates`
   - **Usage**: `{{ matrixTemplates({ matrix }) }}`
   - **Description**: Loads templates for matrix rows.

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

## Example Usages

### Basic Usage

```twig
{{ itemTemplates({ entry }) }}

```twig
{{ matrixTemplates({ matrix }) }}

### Advanced Usage
Append URL paramters to the URL to render debug info while in devMode

?showPathParam=true&showHierarchyParam=true
