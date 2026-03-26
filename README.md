# Rydeen Dealer Portal

B2B dealer portal for Rydeen car accessories, built on Bagisto v2.3.16 + B2B Suite.

## Prerequisites

- PHP 8.2+ with extensions: `calendar`, `curl`, `intl`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.0+

## Local Setup

### 1. Clone the repository

```bash
git clone https://github.com/your-org/bagisto-rydeen.git
cd bagisto-rydeen
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node dependencies

```bash
npm install
```

### 4. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database credentials:

```
DB_DATABASE=bagisto_rydeen
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE bagisto_rydeen;"
```

### 6. Run migrations and seed

```bash
php artisan migrate
php artisan db:seed
```

### 7. Create storage symlink

```bash
php artisan storage:link
```

### 8. Build frontend assets

```bash
npm run build
```

### 9. Start the dev server

```bash
php artisan serve
```

The app will be available at `http://localhost:8000`. The admin panel is at `http://localhost:8000/admin`.

## Custom Packages

Located in `packages/Rydeen/`:

| Package     | Purpose                              |
|-------------|--------------------------------------|
| **Core**    | Shared helpers and base config       |
| **Auth**    | Dealer authentication & device trust |
| **Pricing** | Tiered dealer pricing logic          |
| **Dealer**  | Dealer dashboard and profiles        |

## Useful Commands

```bash
php artisan optimize:clear          # Clear all caches
php artisan test packages/Rydeen/   # Run Rydeen package tests
npm run dev                         # Vite dev server with HMR
npm run build                       # Production asset build
```

## Troubleshooting

- **503 errors on admin?** The built-in PHP server is single-threaded by default. Use `PHP_CLI_SERVER_WORKERS=8 php artisan serve` to handle concurrent requests.
- **Stale views/config?** Run `php artisan optimize:clear`.
- **Missing storage symlink?** Run `php artisan storage:link`.
