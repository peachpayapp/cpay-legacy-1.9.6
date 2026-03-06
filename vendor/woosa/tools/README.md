## Introduction

This module adds a **Tools** section in the settings, displaying useful utilities related to the plugin. The default tools include:

* Clear cache
* Allow long-running requests (hidden by default)

## Installation

* Run composer `require woosa/tools:<version>` in the plugin's root directory. Alternatively, you can add `"woosa/tools": "<version>"` directly in `composer.json` and then run `npm start`

## Usage

How to add a tool to the list:

```php
add_filter(PREFIX . '\module\tools\list', 'my_custom_tools');

function my_custom_tools($list){

   $list = array_merge($list, [
      [
         'id'          => 'my_tool_id',
         'name'        => __('Tool name', '_wsa_text_domain_'),
         'description' => __('Tool description.', '_wsa_text_domain_'),
         'warning'     => __('A warning message, if necessary.', '_wsa_text_domain_'), //(optional)
         'hidden'      => true, //(optional) whether or not to hide the tool
         'btn_class'   => 'my-button-class', //(optional)
         'btn_label'   => 'My Button Label', //(optional)
      ]
   ]);

   return $list;
}
```

How to run a tool:

```php
add_filter(PREFIX . '\module\tools\run_tool', 'run_my_custom_tools');

function run_my_custom_tools($id){

   if('my_tool_id' === $id){
      //run your code here
   }
}
```