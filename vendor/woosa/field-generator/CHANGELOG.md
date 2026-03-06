## 1.5.1 - 2024-10-31

### Fixed

* The value of price addition is not saved

## 1.5.0 - 2024-10-29

### Added

* The field `Use WooCommerce price` has now the same look as the toggle field

## 1.4.1 - 2024-10-21

### Changed

* Use options for `field_value_source`

### Fixed

* Fix Quill by using the CDN files

## 1.4.0 - 2024-08-28

### Changed

* Do not use a prefix for the data attribute of the submit button
* Upgrade Quill to `v2.0.2`

### Fixed

* The field type `field_value_source` retrieves the value from database with static default insead to use `value` property
* `Select2` attribute is added by defaut while it should come from `custom_attributes`

## 1.3.2 - 2024-08-22

### Fixed

* When a checkbox field is disabled, its value is replaced with the `no` value

## 1.3.1 - 2024-08-05

### Fixed

* The name of the field `use_wc_price` is not added in the array

## 1.3.0 - 2024-07-29

### Changed

* The disabled fields display its label as disabled as well

## 1.2.2 - 2023-11-06

### Fixed

* The output template for `multiselect` field type is missing

## 1.2.1 - 2023-10-25

### Fixed

* Get field value from field data or from DB

## 1.2.0 - 2023-09-14

### Added

* Wrappers around the fields
* New field types: `toggle`, `use_wc_price`, `field_value_source`, `submit_button`
* Retrieve field value from DB instead from passed data
* Use for each field a dedicated template file
* Allow to wrap fields in an array

## 1.1.1 - 2023-04-27

### Fixed

* This condition `if(typeof select2 === "function"){` is wrong and never returns true

## 1.1.0 - 2023-03-16

### Fixed

* Fix the typo of property `$context`
* Add missing end section
* Add missing tooltip description
* Add hidden field for checkbox with `no` value

## 1.0.2 - 2022-11-02

* [FIX] - Not showing value `0` for number or text fields

## 1.0.1 - 2022-09-13

* [FIX] - Solve the error `$(...).select2() is not a function`