#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT=18083
HOST="http://127.0.0.1:$PORT"
php -S "127.0.0.1:$PORT" -t public tests/router.php > /tmp/ssf-integration.log 2>&1 &
PID=$!
COOKIE=$(mktemp)
HTML=$(mktemp)
STAMP="$(date +%s)-$$"
cleanup() { kill "$PID" 2>/dev/null || true; rm -f "$COOKIE" "$HTML"; }
trap cleanup EXIT
sleep 2
for type in etudiant enseignant; do
  email="ssf-check-$type-$STAMP@example.com"
  curl --fail --silent --show-error -c "$COOKIE" "$HOST/" -o "$HTML"
  csrf=$(php -r '$h=file_get_contents($argv[1]);preg_match("/name=\"csrf\" value=\"([^\"]+)\"/",$h,$m);echo $m[1]??"";' "$HTML")
  test -n "$csrf" || { echo "ECHEC: jeton CSRF introuvable"; exit 1; }
  code=$(curl --silent --show-error -b "$COOKIE" -o /dev/null -w '%{http_code}' -X POST "$HOST/inscription" \
    --data-urlencode "csrf=$csrf" --data-urlencode "type=$type" \
    --data-urlencode "name=Test Integration SSF" --data-urlencode "email=$email" \
    --data-urlencode "phone=0700000000" --data-urlencode "consent=1")
  echo "$type : HTTP $code"
  test "$code" = 303 || exit 1
done
php <<'PHP'
<?php
require 'src/bootstrap.php';
$pdo=ssf_db();
$rows=$pdo->query("SELECT type,COUNT(*) AS n FROM registrations WHERE full_name='Test Integration SSF' AND email LIKE 'ssf-check-%@example.com' GROUP BY type")->fetchAll();
foreach ($rows as $r) echo $r['type'].': '.$r['n']." inscription(s) de test présentes\n";
$stmt=$pdo->prepare("DELETE FROM registrations WHERE full_name=? AND email LIKE 'ssf-check-%@example.com'");
$stmt->execute(['Test Integration SSF']);
echo 'Données de test supprimées : '.$stmt->rowCount()."\n";
if (count($rows)!==2) exit(1);
PHP
echo "SUCCES: les deux profils ont été testés."
