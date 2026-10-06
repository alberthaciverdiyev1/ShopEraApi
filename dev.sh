#!/usr/bin/env bash
# Snaker — local dev tek komut
# Kullanim: bash dev.sh
set -e
cd "$(dirname "$0")"

API_HOST=127.0.0.1
API_PORT=8000
ADMIN_PORT=5173
STORE_PORT=5174

echo "==> Hazirlik: migration + cache"
php artisan migrate --force
php artisan optimize:clear

echo "==> Servisler basliyor (Ctrl+C hepsini durdurur)"
trap 'kill 0' EXIT

# 1) Laravel API + admin panel
php artisan serve --host=$API_HOST --port=$API_PORT &

# 2) Kuyruk isleri (CJ sync gibi uzun isler icin timeout uzatildi)
php artisan queue:listen --tries=1 --timeout=3600 &

# 3) Admin panel asset'leri (kok Vite)
npm run dev -- --port $ADMIN_PORT &

# 4) SvelteKit storefront
( cd resources/frontend && API_PROXY_TARGET="http://$API_HOST:$API_PORT" npm run dev -- --port $STORE_PORT ) &

echo ""
echo "API        : http://$API_HOST:$API_PORT/api"
echo "Admin panel: http://$API_HOST:$API_PORT/admin"
echo "Admin Vite : http://$API_HOST:$ADMIN_PORT"
echo "Storefront : http://$API_HOST:$STORE_PORT"
wait
