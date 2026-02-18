# Tension

A trivia game built with Laravel and Livewire.

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm
- SQLite

## Local Development

```bash
# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start dev servers
composer dev
```

The app will be available at http://localhost:8000

## Docker

### Quick Start

```bash
# Build and run
docker compose up -d --build

# Run migrations (first time only)
docker exec tension-app php artisan migrate --force
```

The app will be available at http://localhost:8888

### Environment Variables

Create a `.env` file or set these in `docker-compose.yml`:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_KEY` | Laravel app key (required) | - |
| `APP_URL` | Public URL of the app | `http://localhost:8888` |
| `APP_ENV` | Environment | `production` |
| `APP_DEBUG` | Debug mode | `false` |

Generate an app key:
```bash
php artisan key:generate --show
```

### Stopping the Container

```bash
docker compose down
```

## Tech Stack

- Laravel 12
- Livewire 3 / Volt
- SQLite
- Vite
