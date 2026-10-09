#!/usr/bin/env bash
#
# ShopEra — lokal inkişaf mühiti.
# Konfiqi təmizləyir, (default) miqrasiyaları işlədir, sonra API + queue +
# SvelteKit vitrin + admin Vite-i birlikdə başladır.
#
# İstifadə:
#   ./dev.sh                 # migrate + api + queue + svelte + admin
#   SKIP_MIGRATE=1 ./dev.sh  # miqrasiyanı atla
#   SKIP_ADMIN=1 ./dev.sh    # admin Vite-i başlatma
#
# Qeyd: API-ni docker nginx-proxy (shopera.test, port 80) ilə işlədirsinizsə,
#       aşağıdakı "php artisan serve" sətrini və API_PROXY_*/INTERNAL_API_URL
#       dəyişənlərini silin — vite.config.ts default proxy işləyəcək.
#
set -euo pipefail

cd "$(dirname "$0")"

# --- Portlar -----------------------------------------------------------------
API_PORT="${API_PORT:-8000}"
SVELTE_PORT="${SVELTE_PORT:-5173}"
ADMIN_PORT="${ADMIN_PORT:-5175}"

# --- Ön yoxlamalar -----------------------------------------------------------
command -v php >/dev/null 2>&1 || { echo "✗ php tapılmadı (PATH-a əlavə edin)."; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "✗ npm tapılmadı (Node 20+ qurun)."; exit 1; }
[ -d vendor ] || { echo "✗ vendor/ yoxdur → 'composer install' işlədin."; exit 1; }
[ -d node_modules ] || { echo "✗ node_modules yoxdur → 'npm install' işlədin."; exit 1; }
[ -d resources/frontend/node_modules ] || { echo "✗ resources/frontend/node_modules yoxdur → 'npm --prefix resources/frontend install' işlədin."; exit 1; }

echo "› Konfiq təmizlənir…"
php artisan config:clear

if [ "${SKIP_MIGRATE:-0}" != "1" ]; then
  echo "› Miqrasiyalar işlədilir…"
  php artisan migrate --force
else
  echo "› Miqrasiya atlanıldı (SKIP_MIGRATE=1)."
fi

# --- Başladılacaq proseslər --------------------------------------------------
#              name    colour       command
NAMES="api,queue,svelte"
COLORS="#93c5fd,#c4b5fd,#fb7185"
CMDS=(
  "php artisan serve --host=127.0.0.1 --port=${API_PORT}"
  "php artisan queue:work --tries=1"
  "API_PROXY_TARGET=http://127.0.0.1:${API_PORT} API_PROXY_HOST=localhost INTERNAL_API_URL=http://127.0.0.1:${API_PORT}/api npm --prefix resources/frontend run dev -- --port ${SVELTE_PORT} --strictPort"
)

if [ "${SKIP_ADMIN:-0}" != "1" ]; then
  NAMES="${NAMES},admin"
  COLORS="${COLORS},#fdba74"
  CMDS+=("npm run dev -- --port ${ADMIN_PORT} --strictPort")
fi

echo "› Başladılır: ${NAMES} (API :${API_PORT}, Svelte :${SVELTE_PORT}$([ "${SKIP_ADMIN:-0}" != "1" ] && echo ", Admin :${ADMIN_PORT}"))"
echo "  Vitrin → http://localhost:${SVELTE_PORT}   API → http://127.0.0.1:${API_PORT}/api"

# Bash massivini npx concurrently-ə ötür (hər əmr ayrı arqument).
EXEC=()
for cmd in "${CMDS[@]}"; do EXEC+=("$cmd"); done

exec npx concurrently -c "$COLORS" -n "$NAMES" "${EXEC[@]}" --kill-others
