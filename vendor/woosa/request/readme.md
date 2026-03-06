# Introduction

This module is a wrapper for `wp_remote_request()` and adds some extra features:

* It has pre-defined function for `GET`, `POST`, `DELETE`, `HEAD` and `PUT` methods
* Prevents sending same request multiple times. The lock flag exists for 5 minutes.
* Support for caching the response for `GET` method for 1 day
* Support for using ETag header
* Support for using signature header

## How to use

It supports all the default `$args` from [WP_Http::request](https://developer.wordpress.org/reference/classes/WP_Http/request/). In adition we can also enable cache, ETags or signature

```php
//POST request
Request::POST([
   'headers' => [],
   'body' => '',
])->send('http://example.com');

//GET request with cache - only for GET method
Request::GET([
   'headers' => [],
   'cache' => true,
])->send('http://example.com');

//Request with authorized parameter
Request::POST([
   'headers' => [],
   'authorized' => my_condition_of_authorization(),
   'body' => ''
])->send('http://example.com');

//Request with signature & eTag - this is only used with the plugin is build on our Midlayer and the request must be signed
Request::POST([
   'headers' => [],
   'use_signature' => true,
   'use_etag' => true,
   'body' => ''
])->send('http://example.com');

```