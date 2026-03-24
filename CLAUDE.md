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

## Common Commands

```bash
php artisan serve              # dev server at localhost:8000
php artisan test packages/Rydeen/  # run Rydeen tests
php artisan optimize:clear     # clear all caches
railway up                     # deploy to Railway
```
