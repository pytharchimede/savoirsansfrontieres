<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/src/bootstrap.php';
$username=$argv[1]??'admin';
if(!preg_match('/^[a-zA-Z0-9._-]{3,80}$/',$username)){fwrite(STDERR,"Identifiant invalide\n");exit(1);}
fwrite(STDOUT,"Mot de passe initial pour $username (12 caractères min., une majuscule et un chiffre) : ");
system('stty -echo');
try{$password=rtrim((string)fgets(STDIN),"\r\n");}finally{system('stty echo');fwrite(STDOUT,"\n");}
if(strlen($password)<12||!preg_match('/[A-Z]/',$password)||!preg_match('/[0-9]/',$password)){fwrite(STDERR,"Mot de passe trop faible\n");exit(1);}
$db=ssf_db();
$stmt=$db->prepare('SELECT id FROM admin_groups WHERE name=?');$stmt->execute(['Administrateurs']);$gid=$stmt->fetchColumn();
if(!$gid){fwrite(STDERR,"Migration 002_admin_rbac.sql requise\n");exit(1);}
$db->beginTransaction();
try{
 $stmt=$db->prepare('INSERT INTO admin_users(username,password_hash) VALUES(?,?)');
 $stmt->execute([$username,password_hash($password,PASSWORD_DEFAULT)]);
 $uid=(int)$db->lastInsertId();
 $stmt=$db->prepare('INSERT INTO admin_user_groups(user_id,group_id) VALUES(?,?)');$stmt->execute([$uid,$gid]);
 $db->commit();fwrite(STDOUT,"Compte administrateur créé : $username\n");
}catch(Throwable $e){$db->rollBack();fwrite(STDERR,"Échec : identifiant déjà existant ou migration manquante\n");exit(1);}
