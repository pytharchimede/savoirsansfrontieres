#!/usr/bin/env bash
set -euo pipefail
ROOT=/home/ifmapci
REPO="$ROOT/repositories/savoirsansfrontieres"
APP="$ROOT/ssf-app"
WEB="$ROOT/savoirsansfrontieres.com"
cd "$REPO"
test "$(git branch --show-current)" = main || { echo "ERREUR: se placer sur main avant deploiement"; exit 1; }
test -f public/index.php && test -f database/migrations/001_registrations.sql
test -f "$APP/.env" || { echo "ERREUR: config $APP/.env manquante"; exit 1; }
for file in public/index.php src/bootstrap.php views/home.php; do php -l "$file" || exit 1; done
mkdir -p "$ROOT/backups" "$WEB" "$APP"
stamp=$(date +%Y%m%d-%H%M%S)
tar -czf "$ROOT/backups/ssf-web-$stamp.tar.gz" -C "$WEB" . 
tar -czf "$ROOT/backups/ssf-app-$stamp.tar.gz" -C "$APP" --exclude=.env . 
rsync -a --delete --exclude=.env --exclude=.git/ --exclude=public/ "$REPO/" "$APP/"
mkdir -p "$WEB/assets"
rsync -a "$REPO/public/assets/" "$WEB/assets/"
cp "$REPO/public/.htaccess" "$WEB/.htaccess"
sed "s@dirname(__DIR__)@dirname(__DIR__).'/ssf-app'@g" "$REPO/public/index.php" > "$WEB/index.php"
php -l "$WEB/index.php"
echo "Deploiement fichiers termine. Tester /health et les formulaires."
