#!/usr/bin/env bash
set -euo pipefail
ROOT=/home/ifmapci
REPO="$ROOT/repositories/savoirsansfrontieres"
APP="$ROOT/ssf-app"
WEB="$ROOT/savoirsansfrontieres.com"
cd "$REPO"
test "$(git branch --show-current)" = main || { echo "ERREUR: checkout main requis"; exit 1; }
test -f "$APP/.env" || { echo "ERREUR: configuration absente"; exit 1; }
test -f public/index.php && test -f src/bootstrap.php && test -f views/home.php
for file in public/index.php src/bootstrap.php views/home.php; do php -l "$file"; done
mkdir -p "$ROOT/backups" "$WEB"
stamp=$(date +%Y%m%d-%H%M%S)
tar -czf "$ROOT/backups/ssf-web-$stamp.tar.gz" -C "$WEB" .
tar -czf "$ROOT/backups/ssf-app-$stamp.tar.gz" -C "$APP" --exclude=.env .
rsync -a --exclude=.env --exclude=.git/ --exclude=public/ "$REPO/" "$APP/"
mkdir -p "$WEB/assets"
rsync -a "$REPO/public/assets/" "$WEB/assets/"
cp "$REPO/public/.htaccess" "$WEB/.ssf-routing.htaccess"
cat > "$WEB/index.php" <<'PHP'
<?php
declare(strict_types=1);
require '/home/ifmapci/ssf-app/public/index.php';
PHP
php -l "$WEB/index.php"
echo "Fichiers deployes. Activer la reecriture uniquement apres verification du .htaccess cPanel."
