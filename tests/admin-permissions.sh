#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT=18086; BASE="http://127.0.0.1:$PORT"
TMP=$(mktemp -d)
php -S "127.0.0.1:$PORT" -t public tests/router.php >"$TMP/server.log" 2>&1 &
PID=$!
cleanup() { kill "$PID" 2>/dev/null || true; php -r '
require "src/bootstrap.php";
$db=ssf_db();$s=$db->prepare("DELETE FROM admin_users WHERE username=?");$s->execute([$argv[1]]);
' "$USERNAME" >/dev/null 2>&1 || true; rm -rf "$TMP"; }
USERNAME="ssf_reader_$$"
trap cleanup EXIT
PASSWORD="SSFReader$(date +%s)X9!"
php -r '
require "src/bootstrap.php";
$db=ssf_db();$s=$db->prepare("SELECT id FROM admin_groups WHERE name=?");$s->execute(["Lecteurs"]);$group=$s->fetchColumn();if(!$group)exit(1);
$s=$db->prepare("INSERT INTO admin_users(username,password_hash) VALUES(?,?)");$s->execute([$argv[1],password_hash($argv[2],PASSWORD_DEFAULT)]);
$id=$db->lastInsertId();$s=$db->prepare("INSERT INTO admin_user_groups(user_id,group_id) VALUES(?,?)");$s->execute([$id,$group]);
' "$USERNAME" "$PASSWORD"
sleep 2
csrf=$(curl -fsS -c "$TMP/cookie" "$BASE/admin/login" | php -r '$s=stream_get_contents(STDIN);preg_match("/name=\"csrf\" value=\"([^\"]+)\"/",$s,$m);echo $m[1]??"";')
test -n "$csrf"
code=$(curl -sS -b "$TMP/cookie" -c "$TMP/cookie" -o /dev/null -w '%{http_code}' -X POST "$BASE/admin/login" --data-urlencode "username=$USERNAME" --data-urlencode "password=$PASSWORD" --data-urlencode "csrf=$csrf")
echo "Connexion Lecteur : HTTP $code";test "$code" = 303
for path in /admin /admin/inscriptions; do
 code=$(curl -sS -b "$TMP/cookie" -o /dev/null -w '%{http_code}' "$BASE$path")
 echo "$path : HTTP $code";test "$code" = 200
done
for path in '/admin/inscriptions?export=xlsx' '/admin/inscriptions?export=pdf' /admin/utilisateurs /admin/groupes; do
 code=$(curl -sS -b "$TMP/cookie" -o /dev/null -w '%{http_code}' "$BASE$path")
 echo "$path : HTTP $code";test "$code" = 403
done
echo 'SUCCES : permissions Lecteurs respectées, compte temporaire nettoyé'
