## 1.6.0 - 2024-10-28

### Added

* Support for method `PATCH`
* By default the timeout is set to `5` secounds

### Changed

* The lock time is changed from `5` minutes to `1` miunte

## 1.5.0 - 2024-03-18

### Added

* A dedicated method to generate signatures

### Changed

* The way how `query_params` are added in the URL

## 1.4.1 - 2024-01-11

### Fixed

* Do not set logs if the request is cached
* Do not truncate response in log anymore

* ### Changed

* Cache method `GET` by default

## 1.4.0 - 2023-09-14

### Changed

* Make the method `send()` exandable to simulate request responses

## 1.3.0 - 2023-04-24

### Added

* Add the following headers in case they are not already added: `x-woosa-domain`, `x-woosa-license` , `x-woosa-plugin-version`, `x-woosa-plugin-slug`, `x-woosa-marketplace-name`

## 1.2.0 - 2022-07-15

* [FIX] - Decode the body only if it's a valid json
* [TWEAK] - Increase the max length of a response to 5000
* [FEATURE] - Added support for `HEAD` and `PUT` methods