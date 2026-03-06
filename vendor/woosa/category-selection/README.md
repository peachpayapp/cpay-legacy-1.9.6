# Introduction

This module gives the ability o display a UI for selecting multi-level category.

## Dependencies

* [Util](https://gitlab.com/woosa/wp-plugin-modules/util)

## Setup

* Installing via composer requires only to include the `index.php` file from root in your code
* Replace all occurences of `_wsa_namespace_` with your unique namespace
* Replace all occurences of `_wsa_text_domain_` with your translation text domain

## How to use


Example of how to display the selection on any pages:

```php
$source = 'shop'; //the source of the items - supports `shop` and `service`
$level  = 'leaf'; //`leaf` means until to the last sub-category and `tree` is for an entire level of categories

Module_Category_Selection::render($source, $level)
```

Example of how to display the selection on a WooCommerce product page (this requires [Meta](https://gitlab.com/woosa/wp-plugin-modules/Mmeta) module):

```php
$source     = 'service'; //the source of the items - supports `shop` and `service`
$level      = 'tree'; //`leaf` means until to the last sub-category and `tree` is for an entire level of categories
$product_id = 123;
$meta       = new Module_Meta($product_id); //instance

Module_Category_Selection::render_on_product($source, $level, $meta);
```