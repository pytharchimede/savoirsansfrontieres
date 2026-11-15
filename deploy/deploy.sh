#!/usr/bin/env bash
set -euo pipefail
ROOT=/home/ifmapci
REPO="$ROOT/repositories/savoirsansfrontieres"
APP="$ROOT/ssf-app"
WEB="$ROOT/savoirsansfrontieres.com"
cd "$REPO"
test "$(git branch --show-current)" = main || { echo "ERREUR: branche main requise"; exit 1; }
test -f "$APP/.env" || { echo "ERREUR: .env prive manquant"; exit 1; }
test -f public/index.php && test -f src/bootstrap.php && test -f views/home.php
find public src views -type f -name '*.php' -exec php -l {} \;
mkdir -p "$ROOT/backups" "$WEB"
stamp=$(date +%Y%m%d-%H%M%S)
tar -czf "$ROOT/backups/ssf-web-$stamp.tar.gz" -C "$WEB" .
tar -czf "$ROOT/backups/ssf-app-$stamp.tar.gz" -C "$APP" --exclude=.env .
rsync -a --exclude=.env --exclude=.git/ --exclude=public/ "$REPO/" "$APP/"
mkdir -p "$APP/public" "$WEB/assets"
cp "$REPO/public/index.php" "$APP/public/index.php"
rsync -a "$REPO/public/assets/" "$WEB/assets/"
cat > "$WEB/index.php" <<'PHP'
<?php
declare(strict_types=1);
require '/home/ifmapci/ssf-app/public/index.php';
PHP
php -l "$WEB/index.php"
HT="$WEB/.htaccess"
touch "$HT"
if ! grep -q '^# BEGIN SSF ROUTES$' "$HT"; then
cat >> "$HT" <<'HTACCESS'

# BEGIN SSF ROUTES
Options -Indexes
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
</IfModule>
# END SSF ROUTES
HTACCESS
fi
echo "Deploiement termine : verifier https://savoirsansfrontieres.com/health et le formulaire."
