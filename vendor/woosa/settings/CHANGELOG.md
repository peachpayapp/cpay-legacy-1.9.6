## 2.3.0 - 2024-09-29

### Added

* New hook to enable/disable enqueueing admin assets

### Changed

* Prefix all hooks with `\module\`

### Fixed

* Define field `value` instead of `default` to avoid issue when the field is processed by other plugin module

## 2.2.2 - 2023-01-24

### Changed

* Saving settings suppots the fields inside of the array `{prefix}_fiedls`

## 2.2.1 - 2023-11-06

### Fixed

* The logic for cleaning the settings when plugin is uninstalled is missing

## 2.2.0 - 2023-10-17

### Added

* The filter hook `\settings\page\logo_url` to extend the logo image URL

## 2.1.0 - 2023-09-25

### Added

* Added the action hook `\module\settings\page\sidebar\bottom`

### Changed

* Move here the CSS styles for buttons from Core module
* Define ID for menu items and content page

### Fixed

* All setting fields are saved with the plugin prefix

## 2.0.0 - 2023-09-14

### Added

* New logic of how the page is independently created
* New design and styling
* The JS script `tipTip` for tooltips

## 1.1.1 - 2023-04-19

### Changed

* The description of `Preserve Stock Offset` setting option was adjusted to include a link to our wiki article

## 1.1.0 - 2022-10-25

* [CHANGE] - The interface folder has been removed since we have a dedicated module for holding the interfaces.

## 1.0.6 - 2022-05-05

* [FIX] - Clean settings is not triggered when the plugin is uninstalled
* [TWEAK] - New method `Interface_Hook_Settings::maybe_init()` added to the interface