# Introduction

This module performs a check to ensure all the dependencies of the plugin are met otherwise the plugin will not be activated. It checks the following:

* PHP version
* PHP extensions
* Wordpress version
* Other plugins as dependency


## Setup

* Installing via composer requires only to include the `index.php` file from root in your code
* Replace all occurences of `_wsa_namespace_` with your unique namespace
* Replace all occurences of `_wsa_text_domain_` with your translation text domain

