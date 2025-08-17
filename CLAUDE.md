# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is `laravel-docker`, a Laravel package that provides Docker development environment setup for Laravel projects. The package is inspired by Laravel Sail and creates a containerized development environment with common services.

## Development Commands

### Package Development
- `composer test` - Run tests using Pest
- `composer test-coverage` - Run tests with coverage
- `composer format` - Format code using Laravel Pint
- `composer analyse` - Run static analysis with PHPStan

### Testing Framework
The project uses **Pest** for testing (not PHPUnit). Test files should be created in the `tests/` directory.

## Architecture & Key Components

### Core Package Structure
- **Service Provider**: `LaravelDockerServiceProvider` - Handles package registration and installation
- **Main Class**: `LaravelDocker` - Currently empty base class
- **Facade**: `LaravelDocker` facade for accessing the package

### Installation Flow
The package uses an interactive installation command that:
1. Prompts user for container name
2. Publishes Docker configuration files from `resources/stubs/`
3. Replaces template placeholders in published files with user-provided names
4. Renames the binary script to match the project name

### Published Assets
When installed, the package publishes:
- `.docker/` directory - Docker configuration
- `compose.dev.yml` - Docker Compose configuration with services:
  - Application container (PHP/Laravel)
  - MariaDB database
  - Redis cache
  - Mailpit email testing
  - MinIO object storage
  - Meilisearch full-text search
- `binary` script - Renamed to project name, provides command shortcuts

### Binary Script Features
The published binary script provides Laravel Sail-like commands:
- `php`, `composer`, `artisan` - Run commands in application container
- `test`, `phpunit`, `pest` - Run different test suites
- `pint` - Code formatting
- `tinker` - Laravel REPL
- `shell`/`bash` - Container shell access
- `mariadb`, `redis` - Database CLI access
- `share` - Expose local site using expose server
- `open` - Open application in browser
- Standard Docker Compose operations

### Template System
The package uses a template replacement system:
- `timodewinter-laravel-docker` → User's project name (slugged)
- `TDW_LARAVEL_DOCKER` → Environment variable prefix (uppercase with underscores)

## Development Workflow

1. Make changes to package code in `src/`
2. Run tests: `composer test`
3. Format code: `composer format`
4. Check static analysis: `composer analyse`
5. Test installation flow manually if needed

## Package Dependencies
- `spatie/laravel-package-tools` - Package development utilities
- `illuminate/contracts` - Laravel framework contracts
- Uses Pest, Pint, and PHPStan for development tools