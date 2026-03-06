## 1.14.1 - 2024-10-28

### Changed

* The default allocated execution time is changed from 50% to 35 seconds

## 1.14.0 - 2024-04-03

### Added

* New methods for displaying alertbox notification

### Changed

* Group all new log methods in a dedicated class
* Group all deprecated methods in one place

### Fixed

* Convert units not working properly especially for file size

## 1.13.0 - 2024-03-20

### Added

* New methods to set the error and debug logs

### Changed

* The method `Util::build_url()` has been changed to support an array of parts from which the URL to be built

### Deprecated

* All the methods which set logs via WooCommerce are deprecated

### Fixed

* Retrieving an empty property from array it uses the default while it should be used only when the property does not exist in array

## 1.12.0 - 2023-12-06

### Added

* New hooks to filter time and memory limits

### Changed

* The allocated memory and time have been changed from `0.6` to `0.3`

## 1.11.0 - 2023-09-25

### Added

* A new method to check for plugin prefix

## 1.10.0 - 2023-09-14

### Added

* Added a new hook to filter the template arguments in the method `Util::get_template()`

### Fixed

* Use `untrailingslashit()` on CSS JS file paths

### Changed

* The allowed exec time and memory limit have been reduced to 60%

## 1.9.0 - 2023-07-07

### Added

* Added a new method `Util_File::build_path()` which will help us to build the file path with the correct separator based on the server OS

## 1.8.5 - 2023-06-07

### Fixed

* Make sure the array props such as: `handle`, `name` or `path` are set before enqueue/register the scripts/styles

## 1.8.4 - 2023-05-11

### Changed

* Do not define the parameter type for the method `remove_backslashes()` instead instruct the method to format the parameter to the required type

## 1.8.3 - 2023-05-04

### Added

* A new method `remove_backslashes()` for removing the backslashes has been added

### Fixed

* Remove possible query parameters from image URL
* Replace `dbDelta()` with `maybe_create_table()` since the function `dbDelta()` has an issue according to this [comment](https://developer.wordpress.org/reference/functions/dbdelta/#comment-5413) which we were also able to replicate it

### Changed

* The method `Util_Status::list()` has been renamed to `Util_Status::get_list()` and defined static

## 1.8.2 - 2023-03-30

### Fixed

* Image unlink on wp_error issue
* Util get returns `null` if a default is set but the value of the property is null

## 1.8.1 - 2023-03-23

### Fixed

* The filters for `get_template()` method are overwriten by the `/includes` folder

## 1.8.0 - 2022-11-17

* [FEATURE] - Added new methods to create, delete or check the existence of a DB table.
* [FEATURE] - Added new method to upload an image from URL.
* [FEATURE] - Overwrites the template with the template found in the plugin's folder `includes/`

## 1.7.0 - 2022-10-25

* [CHANGE] - The interface folder has been removed since we have a dedicated module for holding the interfaces.

## 1.6.0 - 2022-08-03

* [FEATURE] - New method `Util_Price::round_up()` to round the price up

## 1.5.1 - 2022-07-20

* [FIX] - Add default time limit when the `php_time_limit` is equal to `0`

## 1.5.0 - 2022-07-15

* [FEATURE] - New methods `Uti::get_upload_path()` and `Util::get_upload_url()` to retrieve path and URL of the uploads directory

## 1.4.1 - 2022-06-29

* [FIX] - Solve the error `Type error Util::dimension_to_cm number format accepts only float`
* [FIX] - Ensure `Util::get_template()` does not add slashes in absolute or relative path

## 1.4.0 - 2022-05-11

* [FIX] - Check if the price is numeric before to add the calculation
* [FEATURE] - New class `Util_File` dedicated for working with files
* [FEATURE] - New class `Util_Status` dedicated for displaying UI statuses
* [DEPRECATION] - The method `Util::get_status_html()` is deprecated, use `Util::status()->render()` instead
* [DEPRECATION] - The method `Util::status_list()` is deprecated, use `Util::status()->list()` instead

## 1.3.2 - 2022-04-14

* [FIX] - Replace deprecated constant `FILTER_SANITIZE_STRING` with `FILTER_DEFAULT`
* [TWEAK] - Add new method `Util_Array::get_post_content()` to extract from an array sanitized string allowed to be used as a post content

## 1.3.1 - 2022-03-23

* [FIX] - Use `untrailingslashit()` instead of `trim()` for assest paths

## 1.3.0 - 2022-03-09

* [FEATURE] - New method to calculate the price discount - `Util::price()->discount()`
* [DEPRECATION] - The method `Util::calculate_price_with_addition()` is deprecated, use `Util::price()->addition()` instead

## 1.2.2 - 2022-02-28

* [FIX] - Set the max execution time to 300 seconds and the max memory to 2GB