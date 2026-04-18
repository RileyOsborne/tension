#!/bin/bash

# Friction Design Gallery Generator
# This script automates the entire process of generating screenshots.

# 1. Setup Environment
export $(grep -v '^#' .env.dusk | xargs)

echo "--- Setting up Dusk Database ---"
touch database/dusk.sqlite
php artisan migrate:fresh --database=sqlite_dusk --force
php artisan db:seed --class=StarterPackSeeder --database=sqlite_dusk
php artisan db:seed --class=DuskSeeder --database=sqlite_dusk

echo "--- Starting Background Servers ---"
# Start servers and save PIDs
php artisan serve --port=8888 > /dev/null 2>&1 &
SERVE_PID=$!
php artisan reverb:start --port=8989 > /dev/null 2>&1 &
REVERB_PID=$!
VITE_HMR_HOST=localhost npm run dev > /dev/null 2>&1 &
VITE_PID=$!

# Wait for servers to be ready
echo "Waiting for servers to initialize..."
sleep 5

echo "--- Running Design Gallery Test ---"
php artisan dusk tests/Browser/DesignGalleryTest.php

echo "--- Cleaning Up ---"
kill $SERVE_PID $REVERB_PID $VITE_PID
echo "Done! Screenshots are in tests/Browser/screenshots/gallery/"
