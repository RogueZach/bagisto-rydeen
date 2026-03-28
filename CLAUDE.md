# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Project Overview

Rydeen dealer-facing portal built on Bagisto v2.3.16 + B2B Suite. B2B portal for car accessory dealers, not a consumer storefront.

## Prerequisites

- PHP 8.2+, Composer, Node.js 18+, MySQL 8.0+

## Architecture

- **Base:** Bagisto v2.3.16 with `bagisto/b2b-suite` package
- **Custom packages:** `packages/Rydeen/` — Core, Auth, Pricing, Dealer
- **Do NOT modify** `vendor/` or `packages/Webkul/` — use overrides, listeners, view publishing
- **Repository pattern:** Use repositories for data access
- **Events:** Hook into Bagisto events rather than modifying core

## Server / Runtime

- **Local dev:** Use `php artisan serve`. **Production (Railway):** Nginx + PHP-FPM via Dockerfile with Supervisord.
- **Do NOT use Laravel Octane.** Webkul packages rely on static/singleton caches that persist across requests under Octane, causing stale data and cross-dealer data leakage. The `config/octane.php` `flush` array is empty, and fixing this requires modifying `packages/Webkul/`.
- **Octane dependency:** `laravel/octane` remains in `composer.json` — harmless when unused. Do not remove it; Bagisto v2.3 ships with it.

## Deployment

When the user says "deploy": run `railway up` first, then commit and push to GitHub. Always use Railway CLI, never the web dashboard.

## Common Commands

```bash
php artisan serve              # local dev server at localhost:8000
php artisan test packages/Rydeen/  # run Rydeen tests
php artisan optimize:clear     # clear all caches
docker build -t bagisto-rydeen .   # build production Docker image
railway up                     # deploy to Railway
```
