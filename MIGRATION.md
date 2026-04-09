# Migration Guide: v8.x to v9.0

v9.0 includes three breaking changes. This guide covers upgrading from v8.x.

## Breaking Changes

### 1. Default Template Paths

Template base paths now use an underscore prefix to prevent direct URL routing.

| Before (v8) | After (v9) |
|-------------|------------|
| `entry/`    | `_entry/`  |
| `item/`     | `_item/`   |
| `category/` | `_category/` |
| `matrix/`   | `_matrix/` |
| `asset/`    | `_asset/`  |
| `product/`  | `_product/` |

### 2. Plugin Handle

The plugin handle changed from `_bonsai-twig` to `bonsai-twig`. A database migration runs automatically on update.

### 3. Config File Path

Rename your config file:

```text
config/_bonsai-twig.php  ->  config/bonsai-twig.php
```

## Upgrade Steps

### Quick Fix (preserve old paths)

If you want to update the plugin without renaming template directories:

```php
// config/bonsai-twig.php
return [
    'paths' => [
        'entry'    => 'entry',
        'item'     => 'item',
        'category' => 'category',
        'matrix'   => 'matrix',
        'asset'    => 'asset',
        'product'  => 'product',
    ],
];
```

This maps the new defaults back to the old paths. Your existing templates work unchanged.

### Proper Migration (recommended)

1. Rename your config file from `config/_bonsai-twig.php` to `config/bonsai-twig.php`
2. Rename your template directories:

```bash
cd templates
mv entry _entry
mv item _item
mv category _category
mv matrix _matrix
mv asset _asset
mv product _product
```

3. Run `craft migrate/all` to apply the handle migration
4. Run `craft project-config/apply` if using project config

### Partial Migration

You can migrate directories one at a time. Override only the ones you haven't renamed yet:

```php
// config/bonsai-twig.php
return [
    'paths' => [
        'matrix' => 'matrix',   // not renamed yet
        'asset'  => 'asset',    // not renamed yet
    ],
];
```

Omitted keys use the new `_` prefixed defaults.

## New Feature: Configurable Paths

The `paths` setting lets you use any base path for any element type:

```php
// config/bonsai-twig.php
return [
    'paths' => [
        'entry'    => 'components/entries',
        'matrix'   => 'blocks',
    ],
];
```

Per-template `path` param still takes highest precedence:

```twig
{{ entryTemplates({ entry: entry, path: 'custom/path' }) }}
```

## Path Resolution Order

1. Per-template `path` parameter (highest priority)
2. `paths` map in `config/bonsai-twig.php`
3. Default (`_entry`, `_item`, etc.)

## Project Config

The database migration updates the plugin handle automatically. If you use `project.yaml`, you may need to update the plugin key from `_bonsai-twig` to `bonsai-twig` in your project config files.
