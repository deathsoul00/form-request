# deathsoul00/form-request
Laravel Form Request implementing **zend-filter** for sanitization of inputs.

# Requirements
 - **Laravel <= 5.3**
 - **Zend-Filter ^2.7**

# How to install zend-filter component
```bash
    composer require zendframework/zend-filter ^2.7
```

# Installation
Download the .zip and extract the app folder to your laravel app folder

# Usage

To use this class just simply extend the class **App\Http\Requests\FormRequest** and declare a **protected $filters** variable in your class. See example below.

```php
<?php
namespace App\Http\Requests;

use App\Http\Requests\FormRequest;

class UserRequest extends FormRequest
{
    protected $filters = [
        'field1' => \Zend\Filter\StringTrim::class,
        'field2' => \Zend\Filter\StringTrim::class
    ];
    
    // more code below
}
```

If you want to have a multiple filters in a single field. See example below.

```php
<?php
namespace App\Http\Requests;

use App\Http\Requests\FormRequest;

class UserRequest extends FormRequest
{
    protected $filters = [
        'field1' => [
            \Zend\Filter\StringTrim::class => []
            \Zend\Filter\ToInt::class => []
        ],
    ];
    // more code below
}
```

For class reference of zend-filters see: https://zendframework.github.io/zend-filter/standard-filters/

If the **zend-db** filter class requires an option to be pass to its constructor. You can supply an array to it (see to example above).

**This is an opensource project, please feel free to distrubute or modified the code. I did just for fun.**
