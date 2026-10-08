#!/usr/bin/env bash
#
# ShopEra — lokal inkişaf mühiti.
# Migrate edir, sonra API + queue + SvelteKit vitrin + admin Vite-i birlikdə başladır.
#
# İstifadə:  ./dev.sh
#
# Qeyd: API-ni docker nginx-proxy (shopera.test, port 80) ilə işlədirsinizsə,
#       2-ci əmrdəki (php artisan serve) sətri və API_PROXY_*/INTERNAL_API_URL
#       dəyişənlərini silin — default proxy işləyəcək.
#
set -euo pipefail

cd "$(dirname "$0")"

echo "› Konfiq təmizlənir…"
php artisan config:clear

echo "› Miqrasiyalar işlədilir…"
php artisan migrate --force

echo "› API + queue + Svelte + admin başladılır…"
# SvelteKit always on 5173; the admin (root) Vite moves to 5175 so they
# never fight over the default port.
npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" -n api,queue,svelte,admin \
  "php artisan serve --host=127.0.0.1 --port=8000" \
  "php artisan queue:work --tries=1" \
  "API_PROXY_TARGET=http://127.0.0.1:8000 API_PROXY_HOST=localhost INTERNAL_API_URL=http://127.0.0.1:8000/api npm --prefix resources/frontend run dev -- --port 5173 --strictPort" \
  "npm run dev -- --port 5175 --strictPort" \
  --kill-others
