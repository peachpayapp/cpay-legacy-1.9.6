## Introduction

The scope of this module is to speed up the developer's work by offering utility functionality.

## How to use

### Display alertbox message

```php
Util::alertbox()->content('My meessage here')->title('My title here!')->success(); //success alertbox
Util::alertbox()->content('My meessage here')->title('My title here!')->info(); //info alertbox
Util::alertbox()->content('My meessage here')->title('My title here!')->warning(); //warning alertbox
Util::alertbox()->content('My meessage here')->title('My title here!')->error(); //error alertbox
```

### Dealing with ARRAY

Extract a property:

```php
$arr = [
   'key_1' => 'abcd',
   'some_value1',
   'key_2' => [
      111,
      222,
      333
   ],
];

Util::array($arr)->get('key_1'); //outputs 'abcd'
Util::array($arr)->get('0'); //outputs 'some_value1'
Util::array($arr)->get('key_2/1'); //outputs '222'
Util::array($arr)->get('missing_key', 'my_default_val'); //outputs 'my_default_val'
```

Extract a property with specific validation/sanitization:

```php
$arr = [
   'email'        => 'customer\\"@gmail.com',
   'email2'       => 'customer_gmail.com',
   'url'          => 'https://domain-test.com/?action=test',
   'my_key'       => 'meta_key',
   'html_class'   => 'my_class',
   'post_content' => '<p>This is a post content with <b>HMTL</b></p>',
];

Util::array($arr)->get_email('email'); //outputs 'customer@gmail.com'
Util::array($arr)->get_email('email2'); //outputs 'false' - invalid email
Util::array($arr)->get_url('url');
Util::array($arr)->get_key('my_key');
Util::array($arr)->get_html_class('html_class');
Util::array($arr)->get_post_content('post_content');
```

### Convert units

```php
//convert weight - outputs 2
Util::convert(2000)->from('g')->to('kg');
//convert dimension - outputs 1.5
Util::convert(150)->from('cm')->to('m');
//convert file size - outputs 2.93
Util::convert(3000)->to('KB');
```

### Database table

Check if a database table exists:

```php
Util_DB_Table::is_created('my_table_name');
```

Create a database table:

```php
Util_DB_Table::create('my_table_name',
   'id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
   sku varchar(191) NOT NULL UNIQUE,
   created_at timestamp NOT NULL DEFAULT current_timestamp()'
);
```

Delete a database table:

```php
Util_DB_Table::delete('my_table_name');
```

### Dealing with files

Download a remote file:

```php
Util::file()->remote_download($remote_url, $local_path);
```

Download remote image and assign it to a post:

```php
Util::file()->download_image_from_url($remote_url, $post_id);
```

### Dealing with logs

```php
Util::log()->debug('My content here', __FILE__, __LINE__);
Util::log()->error('My content here', __FILE__, __LINE__);
Util::log()->warning('My content here', __FILE__, __LINE__);
```

### Miscellaneous

Convert object to array:

```php
Util::obj_to_arr($object);
```

Check whether or not a string is json:

```php
Util::is_json($string);
```

Decode a string if is a valid JSON:

```php
Util::maybe_decode_json($string);
```

Prints a variable (especially arrays/objects) in a readable way:

```php
Util::print($variable);
```

Prefix a string with the plugin prefix:

```php
Util::prefix($string);
```

Remove the plugin prefix from a string:

```php
Util::unprefix($string);
```

Converts a string (e.g. 'yes' or 'no') to bool:

```php
//these values will return true, any other than these will return false
Util::string_to_bool('yes');
Util::string_to_bool('true');
Util::string_to_bool('1');
Util::string_to_bool(1);
```

Generates a random string:

```php
Util::random_string();//R4ttreD5rf
```

Registers and enqueues the given JS/CSS files.

```php
//register and enqueue CSS/JS files with the same name and prefix
Uti::enqueue_scripts([
   [
      'name' => 'admin_file',
      'css' => [
         'path' => 'my_path/to_the_folder_file/'//output: my_path/to_the_folder_file/{prefix}-admin_file.css
      ],
      'js' => [
         'path' => 'my_path/to_the_folder_file/'//output: my_path/to_the_folder_file/{prefix}-admin_file.js
      ]
   ]
]);

//register and enqueue CSS/JS files with different names and prefix
Uti::enqueue_scripts([
   [
      'css' => [
         'name' => 'admin_css_file',
         'path' => 'my_path/to_the_folder_file/'//output: my_path/to_the_folder_file/{prefix}-admin_css_file.css
      ],
      'js' => [
         'name' => 'admin_js_file',
         'path' => 'my_path/to_the_folder_file/'//output: my_path/to_the_folder_file/{prefix}-admin_js_file.js
      ]
   ]
]);

//register and enqueue CSS/JS files with no prefix in the name
Uti::enqueue_scripts([
   [
      'css' => [
         'handle' => 'admin_css_file',
         'path' => 'my_path/to_the_folder_file/'//output: my_path/to_the_folder_file/admin_css_file.css
      ],
      'js' => [
         'handle' => 'admin_js_file',
         'path' => 'my_path/to_the_folder_file/'//output: my_path/to_the_folder_file/admin_js_file.js
      ]
   ]
]);

//enqueue only - no register
Uti::enqueue_scripts([
   [
      'css' => [
         'handle' => 'admin_css_file',
         'register' => false
      ],
      'js' => [
         'handle' => 'admin_js_file',
         'register' => false
      ]
   ]
]);

//enqueue conditionally
Uti::enqueue_scripts([
   [
      'css' => [
         'handle' => 'admin_css_file',
         'enqueue' => is_checkout()
      ],
      'js' => [
         'handle' => 'admin_js_file',
         'enqueue' => is_checkout()
      ]
   ]
]);
```