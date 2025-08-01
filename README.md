# Laravel Docker
[![Latest Version on Packagist](https://img.shields.io/packagist/v/timo-de-winter/laravel-docker.svg?style=flat-square)](https://packagist.org/packages/timo-de-winter/laravel-docker)
[![Total Downloads](https://img.shields.io/packagist/dt/timo-de-winter/laravel-docker.svg?style=flat-square)](https://packagist.org/packages/timo-de-winter/laravel-docker)

A package that adds docker features to your project for local development.

**Please note that I use this package myself as it has the features that I use in almost all my projects. It may contain things you do not need, for a more personalized experience, take a look at Laravel Sail.**

## Installation
You can install the package via composer:

```bash
composer require timo-de-winter/laravel-docker
```

After installing the composer package, you should run the install command:
```bash
php artisan docker:install
```

## Usage
You can simply up your container like this:
```bash
bash ./your-project-name up -d
```

For further features, please take a look at Laravel Sail, which this package is based upon.
