<?php
declare(strict_types=1);
final class AdminAuth {
 public static function user(): ?array {
  if (empty($_SESSION['admin_id'])) return null;
  $db=ssf_db();
  $stmt=$db->prepare('SELECT id,username FROM admin_users WHERE id=? AND active=1');
  $stmt->execute([(int)$_SESSION['admin_id']]);
  $user=$stmt->fetch();
  if (!$user) { unset($_SESSION['admin_id']); return null; }
  return $user;
 }
 public static function permissions(int $id): array {
  $stmt=ssf_db()->prepare('SELECT DISTINCT p.code FROM admin_permissions p JOIN admin_group_permissions gp ON gp.permission_id=p.id JOIN admin_user_groups ug ON ug.group_id=gp.group_id WHERE ug.user_id=?');
  $stmt->execute([$id]);return $stmt->fetchAll(PDO::FETCH_COLUMN);
 }
 public static function can(string $permission): bool {
  $u=self::user();return $u!==null && in_array($permission,self::permissions((int)$u['id']),true);
 }
 public static function require(string $permission): void {
  if (!self::user()) { header('Location: /admin/login',true,302);exit; }
  if (!self::can($permission)) { http_response_code(403);exit('Accès refusé'); }
 }
 public static function csrf(): string { return $_SESSION['admin_csrf']??=bin2hex(random_bytes(32)); }
 public static function checkCsrf(): void {
  if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(self::csrf(),$_POST['csrf'])) { http_response_code(419);exit('Session expirée'); }
 }
 public static function login(string $username,string $password): bool {
  $db=ssf_db();$username=trim($username);
  $ipHash=hash('sha256',($_SERVER['REMOTE_ADDR']??'unknown').ssf_env('APP_KEY','ssf'));
  $stmt=$db->prepare('SELECT COUNT(*) FROM admin_login_attempts WHERE attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE) AND (username=? OR ip_hash=?)');
  $stmt->execute([$username,$ipHash]);
  if ((int)$stmt->fetchColumn()>=8) { http_response_code(429);return false; }
  $stmt=$db->prepare('SELECT id,password_hash FROM admin_users WHERE username=? AND active=1');
  $stmt->execute([$username]);$u=$stmt->fetch();
  if (!$u || !password_verify($password,$u['password_hash'])) {
   $stmt=$db->prepare('INSERT INTO admin_login_attempts(username,ip_hash) VALUES(?,?)');
   $stmt->execute([$username,$ipHash]);return false;
  }
  session_regenerate_id(true);
  $_SESSION['admin_id']=(int)$u['id'];
  unset($_SESSION['admin_csrf']);
  $stmt=$db->prepare('UPDATE admin_users SET last_login_at=NOW() WHERE id=?');$stmt->execute([$u['id']]);
  $stmt=$db->prepare('DELETE FROM admin_login_attempts WHERE username=? OR ip_hash=?');$stmt->execute([$username,$ipHash]);
  return true;
 }
}
