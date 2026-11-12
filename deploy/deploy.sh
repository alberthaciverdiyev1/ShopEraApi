#!/usr/bin/env bash
#
# ShopEra — tek komutta tam deploy: API (Laravel) + Blade storefront + admin panel.
#
# Kullanim:
#   bash deploy/deploy.sh                          # mevcut checkout uzerinde deploy
#   PULL=1 bash deploy/deploy.sh                   # once origin/main'den git pull
#   PULL=1 sudo -E bash deploy/deploy.sh           # + izinleri duzelt (root)
#   SKIP_ASSETS=1 bash deploy/deploy.sh            # asset build'ini atla
#
# GitHub push'ta otomatik: .github/workflows/deploy.yml (SSH ile bu script'i cagirir).
#
set -euo pipefail

# ── Ayarlar (env ile ezilebilir) ─────────────────────────────────────────────
APP_DIR="${APP_DIR:-/var/www/ShopEra}"
APP_USER="${APP_USER:-www-data}"
APP_GROUP="${APP_GROUP:-www-data}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
NPM_BIN="${NPM_BIN:-npm}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

PULL="${PULL:-0}"                       # 1 => once origin/<branch>'i cek
SKIP_ASSETS="${SKIP_ASSETS:-0}"         # 1 => root Vite build'ini atla
RUN_MIGRATIONS="${RUN_MIGRATIONS:-1}"   # 0 => migrate atla

PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-}"                  # bos => otomatik algila
QUEUE_SERVICE="${QUEUE_SERVICE:-shopera-worker}"        # systemd unit (varsa)
QUEUE_PROGRAM="${QUEUE_PROGRAM:-}"                      # supervisor program adi (opsiyonel)

# ── Yardimcilar ──────────────────────────────────────────────────────────────
info() { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ✓\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !!\033[0m %s\n' "$*" >&2; }

has() { command -v "$1" >/dev/null 2>&1; }

unit_exists() {
    has systemctl && systemctl cat "$1.service" >/dev/null 2>&1
}

resolve_php_fpm() {
    [ -n "$PHP_FPM_SERVICE" ] && return 0
    for candidate in php8.4-fpm php8.3-fpm php8.2-fpm; do
        if unit_exists "$candidate"; then PHP_FPM_SERVICE="$candidate"; return 0; fi
    done
    PHP_FPM_SERVICE="php8.3-fpm"
}

reload_php_fpm() {
    if unit_exists "$PHP_FPM_SERVICE"; then
        info "PHP-FPM yenileniyor: $PHP_FPM_SERVICE"
        systemctl reload "$PHP_FPM_SERVICE" 2>/dev/null || systemctl restart "$PHP_FPM_SERVICE"
        ok "php-fpm"
    else
        warn "systemd unit yok: $PHP_FPM_SERVICE (atlandi)"
    fi
}

restart_queue() {
    if [ -n "$QUEUE_SERVICE" ] && unit_exists "$QUEUE_SERVICE"; then
        info "Kuyruk worker yenileniyor: $QUEUE_SERVICE"
        systemctl restart "$QUEUE_SERVICE" && ok "queue"
    elif [ -n "$QUEUE_PROGRAM" ] && has supervisorctl; then
        info "Kuyruk worker yenileniyor (supervisor): $QUEUE_PROGRAM"
        supervisorctl restart "$QUEUE_PROGRAM" >/dev/null 2>&1 && ok "queue" \
            || warn "supervisor program bulunamadi: $QUEUE_PROGRAM (atlandi)"
    else
        warn "kuyruk servisi bulunamadi (queue atlandi)"
    fi
}

# ── Baslangic ────────────────────────────────────────────────────────────────[ -d "$APP_DIR" ] || { warn "APP_DIR bulunamadi: $APP_DIR"; exit 1; }
cd "$APP_DIR"
resolve_php_fpm

echo "──────────────────────────────────────────────"
echo " ShopEra deploy  •  $(date '+%Y-%m-%d %H:%M:%S')"
echo " dizin : $APP_DIR"
echo " branch: $BRANCH"
echo "──────────────────────────────────────────────"

# 1) Kod guncellemesi
if [ "$PULL" = "1" ]; then
    info "Git guncelleniyor (origin/$BRANCH)"
    git fetch --prune origin
    git checkout "$BRANCH" 2>/dev/null || git checkout -B "$BRANCH" "origin/$BRANCH"
    git reset --hard "origin/$BRANCH"
    ok "commit: $(git rev-parse --short HEAD)"
fi

# 2) PHP bagimliliklari
info "Composer bagimliliklari"
"$COMPOSER_BIN" install --no-dev --no-interaction --prefer-dist --optimize-autoloader
ok "vendor"

# 3) Admin + uygulama assetleri (root Vite)
if [ "$SKIP_ASSETS" != "1" ]; then
    info "Admin/uygulama assetleri (npm ci + vite build)"
    "$NPM_BIN" ci --no-audit --no-fund
    "$NPM_BIN" run build
    ok "public/build"
else
    warn "asset build atlandi (SKIP_ASSETS=1)"
fi

# 4) Veritabani
if [ "$RUN_MIGRATIONS" = "1" ]; then
    info "Migration"
    "$PHP_BIN" artisan migrate --force
    ok "migrate"
fi

if [ ! -L public/storage ]; then
    info "storage:link"
    "$PHP_BIN" artisan storage:link || warn "storage:link basarisiz"
fi

# 5) Cache
# NOT: `optimize:clear` içindeki `cache:clear` mevcut cache'i tamamen siler;
# bu yüzden hedefli temizlik yapıyoruz.
info "Cache yenile (config/route/view + optimize)"
"$PHP_BIN" artisan config:clear >/dev/null 2>&1 || true
"$PHP_BIN" artisan route:clear >/dev/null 2>&1 || true
"$PHP_BIN" artisan view:clear >/dev/null 2>&1 || true
"$PHP_BIN" artisan optimize
ok "config/route/view cache"

# 6) Izinler (yalnizca root iken)
if [ "$(id -u)" -eq 0 ] && [ -f deploy/scripts/fix-permissions.sh ]; then
    info "Izinler duzeltiliyor"
    APP_DIR="$APP_DIR" APP_USER="$APP_USER" APP_GROUP="$APP_GROUP" \
        bash deploy/scripts/fix-permissions.sh
    ok "permissions"
fi

# 7) Servisleri yenile
restart_queue
reload_php_fpm

echo "──────────────────────────────────────────────"
ok "Deploy tamamlandi → commit $(git rev-parse --short HEAD 2>/dev/null || echo '?')"
echo "──────────────────────────────────────────────"
