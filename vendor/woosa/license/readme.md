# Introduction

This module inserts a section in the settings called `License` giving the ability to activate/deactivate the license.

## Setup

* Installing via composer requires only to include the `index.php` file from root in your code
* Replace all occurences of `_wsa_namespace_` with your unique namespace
* Replace all occurences of `_wsa_text_domain_` with your translation text domain

## Constants to be defined

```php
//this ensures uniqueness through the code
define(__NAMESPACE__ . '\PREFIX', '');

//the version of the plugin
define(__NAMESPACE__ . '\VERSION', '');

//the name of the plugin
define(__NAMESPACE__ . '\NAME', '');

//the path to the plugin folder
define(__NAMESPACE__ . '\DIR_PATH', '');

//the name of the plugin folder
define(__NAMESPACE__ . '\DIR_NAME', '');

//the plugin basename - plugin folder and the main file
define(__NAMESPACE__ . '\DIR_BASENAME', DIR_NAME . '/my-plugin.php');

//enable/disable debug
define(__NAMESPACE__ . '\DEBUG', false);
```