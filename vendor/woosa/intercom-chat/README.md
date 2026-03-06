## Introduction

This module gives the ability to integrate the chat of Intercom only in all our plugin setting pages.

## Installation (via composer)

* In case the plugin is developed by using our [boilerplate](https://gitlab.com/woosa/dev-tools/wp-plugin-starter) you only have to either run `composer require woosa/intercome-chat:version` or add `"woosa/intercome-chat": "version"` in the `composer.json` of the plugin then run `npm start`
* In case the plugin is **NOT** developed by using our [boilerplate](https://gitlab.com/woosa/dev-tools/wp-plugin-starter) then you have to:
  * run `composer require woosa/intercome-chat:version`
  * include the `index.php` file from the root in your plugin logic
  * open the `index.php` file and below the line `defined( 'ABSPATH' ) || exit;` define the following constants:
    *  `define(__NAMESPACE__ . '\PREFIX', '');` - this represents your unique prefix
  * replace all occurences of `_wsa_namespace_` with your unique namespace
