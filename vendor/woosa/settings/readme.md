## Introduction

This module extends WooCommerce settings by adding a custom tab. It comes with the following features:

* It has support for IAN field which allows to define the source of IAN code (SKU, product attribute, or custom field)
* It has support for the option `Use WooCommerce price` which allows to set an addition to it as wel

## Dependency

* [Field Generator](https://gitlab.com/woosa/wp-plugin-modules/field-generator)

## How to use

* Defining a setting option with IAN support is done by specifying the field type as `PREFIX .'_ian_source'`
* Defining a setting option with `Use WooCommerce price` support is done by specifying the field type as `PREFIX .'_use_wc_price'`
* Extending the fields or sections is possible via the hooks from: `Module_Settings::get_settings()` and `Module_Settings::get_sections()`