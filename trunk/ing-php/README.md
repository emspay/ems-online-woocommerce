## Requirements

* PHP 5.4 or later.

## Installation

```php
include_once 'ing-php/vendor/autoload.php';
```

## Getting started

First create a new API client with your API key and ING product:

```php
use \GingerPayments\Payment\Ginger;

$client = Ginger::createClient('ing-api-key', 'ing_product');
```

## Main differences with ginger-php
...


## Full documentation
https://github.com/gingerpayments/ginger-php