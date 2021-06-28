# MONEI PHP SDK

The MONEI API is organized around [REST](https://en.wikipedia.org/wiki/Representational_State_Transfer). Our API has predictable resource-oriented URLs, accepts JSON-encoded request bodies, returns JSON-encoded responses, and uses standard HTTP response codes, authentication, and verbs.

This library is intended to help you develop an integration around our API, by using the MONEI PHP Client and it's methods.

## Docs in our portal

**You can find the complete information and details in [our documentation portal](https://docs.monei.net/api/).**

## Requirements

PHP 5.5 and later

## Installation & Usage

### Composer

To install the bindings via [Composer](http://getcomposer.org/), run the following command:

```bash
composer require monei/monei-php-sdk
```

Or add the following to `composer.json`:

```json
{
  "require": {
    "monei/monei-php-sdk": "^0.1.9"
  }
}
```

Then run `composer install`

### Manual Installation

Download the files and include `autoload.php`:

```php
require_once('/path/to/MONEI PHP SDK/vendor/autoload.php');
```

## Tests

To run the unit tests:

```bash
composer install
./vendor/bin/phpunit
```


## Authorization

The MONEI API uses API key to authenticate requests. You can view and manage your API key in the [MONEI Dashboard](https://dashboard.monei.net/settings/api).

For more information about this process, please refer to [our documentation portal](https://docs.monei.net/api/#section/Authentication).



## Getting Started

Please follow the [installation procedure](#installation--usage) and then run the following:

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Instantiate the client using the API key
$monei = new Monei\MoneiClient('YOUR_API_KEY');

try {
    $result = $monei->payments->create([
        'amount' => 1250, // 12.50€
        'orderId' => '100100000001',
        'currency' => 'EUR',
        'description' => 'Items decription',
        'customer' => [
            'email' => 'john.doe@monei.net',
            'name' => 'John Doe'
        ]
    ]);
    print_r($result);
} catch (Exception $e) {
    echo 'Error while creating payment: ', $e->getMessage(), PHP_EOL;
}

?>
```

## Documentation for API Endpoints

For more detailed information about this library and the full list of methods, please refer to [our documentation portal](https://docs.monei.net/api/).
