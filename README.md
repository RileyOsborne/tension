# Tension

A trivia game built with Laravel and Livewire.

## Quick Start (Docker)

Just want to play? Run with Docker:

```bash
docker compose up -d --build
```

The app will be available at http://localhost:8888

Migrations run automatically on first start.

```bash
# To stop
docker compose down
```

## Development Setup

For active development with hot reloading:

### Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite

### Installation

```bash
# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Create database and run migrations
touch database/database.sqlite
php artisan migrate
```

### Running

Start both servers (in separate terminals):

```bash
php artisan serve --port=8888   # http://localhost:8888
npm run dev                     # Vite hot reloading
```

## Tech Stack

- Laravel 12
- Livewire 3 / Volt
- Vite
- SQLite
