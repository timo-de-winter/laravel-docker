{{-- Boost guideline for timo-de-winter/laravel-docker. Update when the install command or stubs change. --}}
# Laravel Docker (Sail-style dev containers)

- Dev-only (require-dev) package whose command {{ $assist->artisanCommand('docker:install') }} scaffolds this app's Docker environment (prompts: name, mariadb/mysql/postgres, AI tools; auto-installs Horizon).

## Structure

- `.docker/dev/` — Dockerfile (Ubuntu 24.04, PHP 8.4, Node 22, supervisord), php.ini.
- `compose.dev.yml` — services: `application`, the chosen database, `redis`, `mailpit`, `minio`, `meilisearch`.
- Project-named binary wrapper at the app root.
- `TimoDeWinter\LaravelDocker\LaravelDockerServiceProvider` — publish tag `docker`; no runtime code.

## Using it

- Run everything through the binary: `./{project} up -d`, `artisan`, `composer`, `npm`, `test`, `pest`, `pint`, `tinker`, `shell`, a DB CLI subcommand matching the chosen database (`mariadb`/`mysql`/`postgres`), `redis`, `debug` (Xdebug), `share`, `open`; other args go to docker compose.
- Scaffolded files are app-owned: edit `.docker/`, `compose.dev.yml`, or the binary. Never rerun `docker:install` — it overwrites `compose.dev.yml` and the binary.

## Pitfalls

- **This package — not Laravel Sail — owns the app's containers.** Never suggest `sail` commands; use the project binary.
