## Introduction

This module adds a section in the plugin settings called `Authorization` and comes with the following:

* It has a UI for inputting the authorization credentials and gives the option to grant or revoke the access
* Used in conjunction with [Logger](https://gitlab.com/woosa/wp-plugin-modules/logger) module it will show a warning log if the plugin is not authorized

## Installation (via composer)

* In case the plugin is developed by using our [boilerplate](https://gitlab.com/woosa/dev-tools/wp-plugin-starter) you only have to either run `composer require woosa/authorization:version` or add `"woosa/authorization": "version"` in the `composer.json` of the plugin then run `npm start`
* In case the plugin is **NOT** developed by using our [boilerplate](https://gitlab.com/woosa/dev-tools/wp-plugin-starter) then you have to:
  * run `composer require woosa/authorization:version`
  * include the `index.php` file from the root in your plugin logic
  * open the `index.php` file and below the line `defined( 'ABSPATH' ) || exit;` define the following constants:
    *  `define(__NAMESPACE__ . '\PREFIX', '');` - this represents your unique prefix
    *  `define(__NAMESPACE__ . '\SETTINGS_TAB_ID', '');` - this is the settings tab id of the plugin where the section will be added, this works only if you use the module [Settings](https://gitlab.com/woosa/wp-plugin-modules/settings) otherwise you have to include it manually in your own logic
    *  `define(__NAMESPACE__ . '\SETTINGS_URL', '');` - the URL to the settings page
    *  `define(__NAMESPACE__ . '\DIR_PATH', '');` - the path to the plugin
  * replace all occurences of `_wsa_namespace_` with your unique namespace
  * replace all occurences of `_wsa_text_domain_` with your translation text domain

## Usage

Example of how to hook on granting access action:

```php
add_filter(PREFIX . '\authorization\connect', 'my_connect_func');

function my_connect_func($output){

   $condition = false;

   //in case my condition fails
   if( ! $condition ){

      $output = [
         'success' => false,
         'message' => 'Granting the access has failed',
      ];

   }

   return $output;
}
```

Example of how to hook on revoking access action:

```php
add_filter(PREFIX . '\authorization\disconnect', 'my_disconnect_func');

function my_disconnect_func($output){

   $condition = false;

   //in case my condition fails
   if( ! $condition ){

      $output = [
         'success' => false,
         'message' => 'Revoking the access has failed',
      ];

   }

   return $output;
}
```