# Introduction

This module gives the ability to generate the HTML of input fields from an array.

## Setup

* Installing via composer requires only to include the `index.php` file from root in your code
* Replace all occurences of `_wsa_namespace_` with your unique namespace
* Replace all occurences of `_wsa_text_domain_` with your translation text domain

## How to use

Example of how to generate input fields:

```php

$fields = [
   [
      'id'       => 'title',
      'name'     => 'Title',
      'type'     => 'text',
      'required' => 0,
      'custom_attributes' => [],
   ],
   [
      'id'       => 'description',
      'name'     => 'Description',
      'type'     => 'editor',
      'required' => 0,
      'custom_attributes' => [],
   ],
   [
      'id'       => 'height',
      'name'     => 'Height',
      'type'     => 'number',
      'required' => 0,
      'custom_attributes' => [],
   ],
];

//with no context
$mfg = new Module_Field_Generator;
$mfg->set_fields($fields);
$mfg->render();

//with context
$mfg = new Module_Field_Generator;
$mfg->set_fields($fields, 'my_context_here');
$mfg->render();

//the context is useful when filtering the fields, having a context will help you to filter the fields only for that context and not for all
```
