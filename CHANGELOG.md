<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

## [9.1.0](https://github.com/wabi-soft/craft-bonsai-twig/compare/v9.0.1...v9.1.0) (2026-04-09)


---

## [9.0.1](https://github.com/wabi-soft/craft-bonsai-twig/compare/v9.0.0...v9.0.1) (2026-04-09)


---

## [9.0.0](https://github.com/wabi-soft/craft-bonsai-twig/compare/v8.0.1...v9.0.0) (2026-04-09)

### BREAKING CHANGES

* Default template paths now use underscore prefix (`_entry`, `_item`, `_category`, `_matrix`, `_asset`, `_product`) to prevent direct URL routing. Rename your template directories or add a `paths` config to preserve old paths.
* Plugin handle renamed from `_bonsai-twig` to `bonsai-twig`. Rename your config file from `config/_bonsai-twig.php` to `config/bonsai-twig.php`.

### Features

* Configurable `paths` map in `config/bonsai-twig.php` to override base paths per element type
* `Settings::getPathForType()` method for shared path resolution across all loaders
* Database migration to update plugin handle automatically

---

## [8.0.1](https://github.com/wabi-soft/craft-bonsai-twig/compare/v8.0.0...v8.0.1) (2026-03-12)


---

## [8.0.0](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.3.3...v8.0.0) (2026-03-12)

### Features

* Type-first template resolution strategy — opt-in `strategy: 'type'` parameter for EntryLoader and ItemLoader
* Three-level configuration: per-template override, config file (`config/bonsai-twig.php`), or Control Panel settings
* Strategy displayed in beastmode overlay and `btPath()` debug output

### Bug Fixes

* Gate _btStrategy behind devMode in loaders ([9dba08](https://github.com/wabi-soft/craft-bonsai-twig/commit/9dba08b4264eb3689402819554f5358f3197b049))


---

## [7.3.3](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.3.2...v7.3.3) (2025-11-06)


---

## [7.3.2](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.3.1...v7.3.2) (2025-11-06)


---

## [7.3.1](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.3.0...v7.3.1) (2025-11-06)

### Bug Fixes

* Stacking order through isolation ([5632e9](https://github.com/wabi-soft/craft-bonsai-twig/commit/5632e99b6170b77b3239098057f25cfd9df35953))


---

## [7.3.0](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.2.1...v7.3.0) (2025-11-06)


---

## [7.2.1](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.2.0...v7.2.1) (2025-11-06)

### Bug Fixes

* UX of overlay ([e72723](https://github.com/wabi-soft/craft-bonsai-twig/commit/e72723325a4fa5da29ed441dcdeea95d291e1e92))


---

## [7.2.0](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.1.1...v7.2.0) (2025-11-04)


---

## [7.1.1](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.1.0...v7.1.1) (2025-11-03)


---

## [7.1.0](https://github.com/wabi-soft/craft-bonsai-twig/compare/v7.0.10...v7.1.0) (2025-10-31)

### Bug Fixes

* Auto formatting “stuff” and abstract field inspector ([857447](https://github.com/wabi-soft/craft-bonsai-twig/commit/857447cdd39e148c9ed0c4bb541938576de63b05))


---

## [Unreleased]

### Added
- Field handles now displayed in beastmode debug information showing available first-level fields on entries, matrix blocks, and items (dev mode only)
- Tabbed interface for beastmode debug window with Templates, Fields, and Context tabs
- Active tab preference persists across page loads and all debug instances using localStorage
- Nested field information for relational fields:
  - Matrix fields show block types and their fields (expandable)
  - Entries fields show allowed sections and handles
  - Categories fields show allowed groups and handles
  - Assets fields show allowed volumes and handles
  - Users fields show user element indicator
- Option-based field information:
  - Dropdown fields show all available options with values
  - Radio Buttons fields show all options with values
  - Checkboxes fields show all options with values
  - Multi-select fields show all options with values
  - Default options are clearly marked
  - Lightswitch fields show boolean indicator

## [1.0.2](https://github.com/wabi-soft/craft-bonsai-twig/compare/v1.0.1...v1.0.2) (2023-06-13)


---

## [1.0.1](https://github.com/wabi-soft/craft-bonsai-twig/compare/v1.0.0...v1.0.1) (2023-06-13)


---

## [0.0.1](https://github.com/wabi-soft/craft-bonsai-twig/compare/0.0.0...v0.0.1) (2023-06-13)


---

