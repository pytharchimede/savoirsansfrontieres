#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT=18085
BASE="http://127.0.0.1:$PORT"
php -S "127.0.0.1:$PORT" -t public tests/router.php >/tmp/ssf-admin-integration.log 2>&1 &
PID=$!
TMP=$(mktemp -d)
trap 'kill "$PID" 2>/dev/null || true; rm -rf "$TMP"' EXIT
sleep 2
csrf=$(curl -fsS -c "$TMP/cookie" "$BASE/admin/login" | php -r '$s=stream_get_contents(STDIN);preg_match("/name=\"csrf\" value=\"([^\"]+)\"/",$s,$m);echo $m[1]??"";')
test -n "$csrf" || { echo 'ECHEC : CSRF login absent'; exit 1; }
code=$(curl -sS -b "$TMP/cookie" -c "$TMP/cookie" -o /dev/null -w '%{http_code}' -X POST "$BASE/admin/login" --data-urlencode 'username=admin' --data-urlencode 'password=admin' --data-urlencode "csrf=$csrf")
echo "Connexion admin : HTTP $code"
test "$code" = 303
code=$(curl -sS -b "$TMP/cookie" -o "$TMP/dashboard" -w '%{http_code}' "$BASE/admin")
echo "Tableau de bord : HTTP $code"
test "$code" = 200
grep -q 'Total des inscrits' "$TMP/dashboard"
code=$(curl -sS -b "$TMP/cookie" -o "$TMP/registrations" -w '%{http_code}' "$BASE/admin/inscriptions")
echo "Inscriptions : HTTP $code"
test "$code" = 200
for path in utilisateurs groupes; do
 code=$(curl -sS -b "$TMP/cookie" -o /dev/null -w '%{http_code}' "$BASE/admin/$path")
 echo "$path : HTTP $code"
 test "$code" = 200
done
code=$(curl -sS -b "$TMP/cookie" -o "$TMP/export.xlsx" -w '%{http_code}' "$BASE/admin/inscriptions?export=xlsx")
echo "Export Excel : HTTP $code"
test "$code" = 200
php -r '$f=file_get_contents($argv[1]);if(substr($f,0,4)!=="PK\x03\x04" || strpos($f,"xl/workbook.xml")===false)exit(1);echo "Structure ZIP XLSX : OK\n";' "$TMP/export.xlsx"
code=$(curl -sS -b "$TMP/cookie" -o "$TMP/export.pdf" -w '%{http_code}' "$BASE/admin/inscriptions?export=pdf")
echo "Export PDF : HTTP $code"
test "$code" = 200
php -r '$f=file_get_contents($argv[1]);if(substr($f,0,5)!=="%PDF-")exit(1);echo "En-tête PDF : OK\n";' "$TMP/export.pdf"
code=$(curl -sS -o /dev/null -w '%{http_code}' "$BASE/admin/inscriptions?export=xlsx")
echo "Export sans session : HTTP $code"
test "$code" = 303
echo 'SUCCES : tests administration terminés'
