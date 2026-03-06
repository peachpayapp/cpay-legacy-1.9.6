## Introduction

This is the most important module because this loads and initiates the other modules and comes with the following:

* It fires hooks for each state: `activated`, `deactivated`, `upgraded` and `uninstalled`
* Used in conjunction with [Module Dependency](#module-dependency) it performs a check for the dependencies before initiating the plugin
* It has util CSS classes - check the file [module-core.css](https://gitlab.com/woosa/wp-plugin-modules/core/-/blob/master/assets/css/module-core.css)
* It initiates util JS scripts - check the file [module-core.js](https://gitlab.com/woosa/wp-plugin-modules/core/-/blob/master/assets/js/module-core.js)
* It gives the ability to insert action links to the plugin (e.g. Settings, Logs, Doc, etc) - check method `Module_Core_Hook::init_plugin_action_links()`
* It loads the translation based on the given text domain
* It sets an instance of the website including the website `url` and `domain`

## Dependency

* [Interface](https://gitlab.com/woosa/wp-plugin-modules/interface)
* [Option](https://gitlab.com/woosa/wp-plugin-modules/option)
* [Util](https://gitlab.com/woosa/wp-plugin-modules/util)

## Installation (via composer)

* In case the plugin is developed by using our [boilerplate](https://gitlab.com/woosa/dev-tools/wp-plugin-starter) you only have to either run `composer require woosa/core:version` or add `"woosa/core": "version"` in the `composer.json` of the plugin then run `npm start`
* In case the plugin is **NOT** developed by using our [boilerplate](https://gitlab.com/woosa/dev-tools/wp-plugin-starter) then you have to:
  * run `composer require woosa/core:version`
  * include the `index.php` file from the root in your plugin logic
  * open the `index.php` file and below the line `defined( 'ABSPATH' ) || exit;` define the following constants:
    *  `define(__NAMESPACE__ . '\PREFIX', '');` - this represents your unique prefix
    *  `define(__NAMESPACE__ . '\DEBUG', '');` - this is for debugging
    *  `define(__NAMESPACE__ . '\DIR_BASENAME', '');` - this is the plugin slug and the main plugin file for example: `my-plugin/my-plugin.php`
    *  `define(__NAMESPACE__ . '\DIR_PATH', '');` - the path to the plugin
  * replace all occurences of `_wsa_namespace_` with your unique namespace
  * replace all occurences of `_wsa_text_domain_` with your translation text domain

## Usage

To initialize the module in your code just add:

```php
Module_Core_Hook::init();
```