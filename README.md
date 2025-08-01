# A package that adds docker features to your project for local development

[![Latest Version on Packagist](https://img.shields.io/packagist/v/timo-de-winter/laravel-docker.svg?style=flat-square)](https://packagist.org/packages/timo-de-winter/laravel-docker)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/timo-de-winter/laravel-docker/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/timo-de-winter/laravel-docker/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/timo-de-winter/laravel-docker/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/timo-de-winter/laravel-docker/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/timo-de-winter/laravel-docker.svg?style=flat-square)](https://packagist.org/packages/timo-de-winter/laravel-docker)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.
## Installation

You can install the package via composer:
```bash
composer require timo-de-winter/laravel-docker
```

You can publish and run the migrations with:
```bash
php artisan vendor:publish --tag="laravel-docker-migrations"
php artisan migrate
```

You can publish the config file with:
```bash
php artisan vendor:publish --tag="laravel-docker-config"
```

This is the contents of the published config file:
```php
return [
];
```

Optionally, you can publish the views using
```bash
php artisan vendor:publish --tag="laravel-docker-views"
```

## Usage
```php
$laravelDocker = new TimoDeWinter\LaravelDocker();
echo $laravelDocker->echoPhrase('Hello, TimoDeWinter!');
```

## Testing
```bash
composer test
```

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities
Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits
- [Timo de Winter](https://github.com/timo-de-winter)
- [All Contributors](../../contributors)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
