#!/usr/bin/env bash
set -euo pipefail
ROOT="/home/ifmapci"
REPO="$ROOT/repositories/savoirsansfrontieres"
TARGET="$ROOT/savoirsansfrontieres.com"
cd "$REPO"
git fetch origin main
git checkout main
git pull --ff-only origin main
test -f public/index.php || { echo "ERREUR: application absente sur main"; exit 1; }
test -f "$TARGET/.env" || { echo "ERREUR: .env production absent"; exit 1; }
mkdir -p "$ROOT/backups"
tar -czf "$ROOT/backups/ssf-$(date +%Y%m%d-%H%M%S).tar.gz" -C "$TARGET" --exclude=.env . || true
rsync -a --delete --exclude=.env --exclude=storage/ --exclude=vendor/ --exclude=.git/ "$REPO/" "$TARGET/"
cd "$TARGET"
if command -v composer >/dev/null; then composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction; fi
find app bootstrap core public routes resources -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
echo "Déploiement terminé. Vérifier docroot public/ et migrations séparément."
