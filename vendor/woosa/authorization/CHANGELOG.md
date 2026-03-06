## 3.1.1 - 2024-10-09

### Fixed

* Update old doc links

## 3.1.0 - 2024-09-18

### Added

* New hooks: `\module\authorization\settings_tab_name`, `\module\authorization\settings_tab_description`

### Changed

* Update Synchronization module hook names
* Remove deprecated `dropsync` hooks

## 3.0.0 - 2023-09-14

### Changed

* Modify the way how the output is insterted for the Settigns module v2

## 2.1.3 - 2023-04-19

### Changed

* A description was added beneath the authorization status which inclues a link to our wiki article

## 2.1.2 - 2022-10-26

* [TWEAK] - Add module interface as dependency

## 2.1.1 - 2022-06-07

* [TWEAK] - Revoke the authorization if the remote request gets 401

## 2.1.0 - 2022-05-03

* [IMPROVEMENT] - New UI for the output section which comes with the status and the submit button, the fields must be added by the plugin via the hook
* [TWEAK] - Implement the new method added ot the settings interface

## 2.0.2 - 2022-03-18

* [FIX] - Save extra fields before init the class and let the env to be defined based on the setting option
* [TWEAK] - Add fallback to `testmode` option