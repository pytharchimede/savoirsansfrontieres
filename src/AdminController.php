<?php
declare(strict_types=1);
final class AdminController {
 private static function esc(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
 private static function go(string $path): never {header('Location: '.$path,true,303);exit;}
 private static function page(string $title,string $body): never {
  $user=AdminAuth::user();$name=self::esc($user['username']??'');
  $nav=$user?'<nav><a href="/admin">Tableau de bord</a> <a href="/admin/inscriptions">Inscriptions</a> '.(AdminAuth::can('users.manage')?'<a href="/admin/utilisateurs">Utilisateurs</a> ':'').(AdminAuth::can('groups.manage')?'<a href="/admin/groupes">Groupes</a> ':'').(AdminAuth::can('catalogs.manage')?'<a href="/admin/referentiels">Matières et niveaux</a> ':'').'<form action="/admin/logout" method="post"><input type="hidden" name="csrf" value="'.AdminAuth::csrf().'"><button>Déconnexion ('.$name.')</button></form></nav>':'';
  header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store');
  echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.self::esc($title).' — SSF Administration</title><link rel="stylesheet" href="/assets/admin.css"></head><body><header><a class="brand" href="/admin">✦ SAVOIR SANS FRONTIÈRES <small>Administration</small></a>'.$nav.'</header><main><h1>'.self::esc($title).'</h1>'.$body.'</main></body></html>';exit;
 }
 private static function token(): string {return '<input type="hidden" name="csrf" value="'.self::esc(AdminAuth::csrf()).'">';}
 public static function route(string $path,string $method): never {
  if($path==='/admin/login'){
   if($method==='POST'){
    AdminAuth::checkCsrf();
    if(AdminAuth::login((string)($_POST['username']??''),(string)($_POST['password']??'')))self::go('/admin');
    self::page('Connexion','<div class="alert">Identifiants invalides ou trop de tentatives. Réessayez ultérieurement.</div>'.self::loginForm());
   }
   self::page('Connexion',self::loginForm());
  }
  if($path==='/admin/logout' && $method==='POST'){AdminAuth::checkCsrf();unset($_SESSION['admin_id']);session_regenerate_id(true);self::go('/admin/login');}
  if(!AdminAuth::user())self::go('/admin/login');
  if($path==='/admin' && $method==='GET'){
   AdminAuth::require('registrations.view');$db=ssf_db();
   $total=(int)$db->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
   $learn=(int)$db->query("SELECT COUNT(*) FROM registrations WHERE type='etudiant'")->fetchColumn();
   $teach=$total-$learn;
   self::page('Tableau de bord','<div class="metrics"><article><span>Total des inscrits</span><strong>'.$total.'</strong></article><article><span>Apprenants</span><strong>'.$learn.'</strong></article><article><span>Enseignants</span><strong>'.$teach.'</strong></article></div><p><a class="btn" href="/admin/inscriptions">Consulter les inscriptions →</a></p>');
  }
  if($path==='/admin/inscriptions' && $method==='GET'){
   AdminAuth::require('registrations.view');$db=ssf_db();
   $type=(string)($_GET['type']??'');if(!in_array($type,['','etudiant','enseignant'],true))$type='';
   $q=trim((string)($_GET['q']??''));$q=mb_substr($q,0,100);
   $where=[];$params=[];
   if($type!==''){$where[]='r.type=?';$params[]=$type;}
   if($q!==''){$where[]='(r.full_name LIKE ? OR r.email LIKE ? OR r.phone LIKE ?)';for($i=0;$i<3;$i++)$params[]='%'.$q.'%';}
   $filter=$where?' WHERE '.implode(' AND ',$where):'';
   $stmt=$db->prepare('SELECT COUNT(*) FROM registrations r'.$filter);$stmt->execute($params);$total=(int)$stmt->fetchColumn();
   $page=max(1,(int)($_GET['page']??1));$perPage=30;$offset=($page-1)*$perPage;
   $export=(string)($_GET['export']??'');
   if($export!==''){AdminAuth::require('registrations.export');if(!in_array($export,['pdf','xlsx'],true)){http_response_code(400);exit('Format invalide');}
    if($total>5000){http_response_code(413);exit('Export limité à 5000 lignes. Affinez vos filtres.');}
    $stmt=$db->prepare('SELECT r.id,r.type,r.full_name,r.email,r.phone,COALESCE(s.label,r.subject) subject,COALESCE(l.label,r.student_level) student_level,r.created_at FROM registrations r LEFT JOIN education_subjects s ON s.code=r.subject LEFT JOIN education_levels l ON l.code=r.student_level'.$filter.' ORDER BY r.created_at DESC,r.id DESC');
    $stmt->execute($params);$rows=$stmt->fetchAll();
    if($export==='xlsx')AdminExport::xlsx($rows);AdminExport::pdf($rows);
   }
   $stmt=$db->prepare('SELECT id,type,full_name,email,phone,subject,student_level,created_at FROM registrations'.$filter.' ORDER BY created_at DESC,id DESC LIMIT 30 OFFSET '.$offset);
   $stmt->execute($params);$rows=$stmt->fetchAll();
   $body='<form method="get" class="filters"><input name="q" placeholder="Nom, email, téléphone" value="'.self::esc($q).'"><select name="type"><option value="">Tous les profils</option><option value="etudiant"'.($type==='etudiant'?' selected':'').'>Apprenants</option><option value="enseignant"'.($type==='enseignant'?' selected':'').'>Enseignants</option></select><button>Filtrer</button></form><p>'.$total.' inscription(s)</p>';
   $query=http_build_query(['q'=>$q,'type'=>$type]);
   if(AdminAuth::can('registrations.export'))$body.='<p><a class="btn" href="/admin/inscriptions?'.$query.'&export=xlsx">Exporter Excel (.xlsx)</a> <a class="btn secondary" href="/admin/inscriptions?'.$query.'&export=pdf">Exporter PDF</a></p>';
   $body.='<div class="tablewrap"><table><thead><tr><th>ID</th><th>Profil</th><th>Nom complet</th><th>Email</th><th>Téléphone</th><th>Matière</th><th>Classe / niveau</th><th>Inscrit le</th></tr></thead><tbody>';
   foreach($rows as $r){$body.='<tr>';foreach(['id','type','full_name','email','phone','subject','student_level','created_at'] as $k)$body.='<td>'.self::esc($r[$k]).'</td>';$body.='</tr>';}
   $body.='</tbody></table></div><div class="pagination">';
   if($page>1)$body.='<a href="?'.$query.'&page='.($page-1).'">← Précédent</a> ';
   $body.='Page '.$page.' / '.max(1,(int)ceil($total/$perPage));
   if($offset+$perPage<$total)$body.=' <a href="?'.$query.'&page='.($page+1).'">Suivant →</a>';
   self::page('Inscriptions',$body.'</div>');
  }

  if($path==='/admin/referentiels'){
   AdminAuth::require('catalogs.manage');$db=ssf_db();
   $catalogs=['subject'=>['education_subjects','Matières'],'level'=>['education_levels','Niveaux d’études']];
   if($method==='POST'){
    AdminAuth::checkCsrf();$kind=(string)($_POST['kind']??'');
    if(!isset($catalogs[$kind])){http_response_code(400);exit('Référentiel invalide');}
    $table=$catalogs[$kind][0];$action=(string)($_POST['action']??'');
    if($action==='create'){
     $label=trim((string)($_POST['label']??''));
     if(mb_strlen($label)<2||mb_strlen($label)>120){http_response_code(422);exit('Libellé invalide');}
     $code='c'.bin2hex(random_bytes(8));
     $s=$db->prepare("INSERT INTO $table(code,label,sort_order) VALUES(?,?,1000)");$s->execute([$code,$label]);
    }elseif($action==='rename'){
     $id=(int)($_POST['id']??0);$label=trim((string)($_POST['label']??''));
     if($id<1||mb_strlen($label)<2||mb_strlen($label)>120){http_response_code(422);exit('Valeur invalide');}
     $s=$db->prepare("UPDATE $table SET label=? WHERE id=?");$s->execute([$label,$id]);
    }elseif($action==='toggle'){
     $id=(int)($_POST['id']??0);if($id<1){http_response_code(422);exit('ID invalide');}
     $s=$db->prepare("UPDATE $table SET active=1-active WHERE id=?");$s->execute([$id]);
    }else{http_response_code(400);exit('Action invalide');}
    self::go('/admin/referentiels');
   }
   $body='<p>Les éléments désactivés ne sont plus proposés aux nouveaux inscrits. Les inscriptions historiques restent conservées.</p>';
   foreach($catalogs as $kind=>[$table,$title]){
    $rows=$db->query("SELECT id,label,active FROM $table ORDER BY sort_order,label")->fetchAll();
    $body.='<h2>'.self::esc($title).'</h2><div class="tablewrap"><table><tr><th>Libellé</th><th>État</th><th>Modifier</th><th>Disponibilité</th></tr>';
    foreach($rows as $item){
     $body.='<tr><td>'.self::esc($item['label']).'</td><td>'.($item['active']?'Actif':'Inactif').'</td><td><form method="post">'.self::token().'<input type="hidden" name="kind" value="'.$kind.'"><input type="hidden" name="action" value="rename"><input type="hidden" name="id" value="'.(int)$item['id'].'"><input name="label" maxlength="120" required value="'.self::esc($item['label']).'"><button>Enregistrer</button></form></td><td><form method="post">'.self::token().'<input type="hidden" name="kind" value="'.$kind.'"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="'.(int)$item['id'].'"><button>'.($item['active']?'Désactiver':'Activer').'</button></form></td></tr>';
    }
    $body.='</table></div><form method="post" class="panel">'.self::token().'<input type="hidden" name="kind" value="'.$kind.'"><input type="hidden" name="action" value="create"><label>Ajouter un élément<input name="label" maxlength="120" required></label><button>Ajouter</button></form>';
   }
   self::page('Matières et niveaux',$body);
  }
  if($path==='/admin/utilisateurs'){
   AdminAuth::require('users.manage');$db=ssf_db();
   if($method==='POST'){
    AdminAuth::checkCsrf();$action=(string)($_POST['action']??'');
    if($action==='create'){
     $username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');$groups=array_map('intval',(array)($_POST['groups']??[]));
     if(!preg_match('/^[a-zA-Z0-9._-]{3,80}$/',$username)||strlen($password)<12||!preg_match('/[A-Z]/',$password)||!preg_match('/[0-9]/',$password)||!$groups){http_response_code(422);exit('Identifiant ou mot de passe invalide : 12 caractères minimum, une majuscule et un chiffre, avec un groupe.');}
     try{$db->beginTransaction();$s=$db->prepare('INSERT INTO admin_users(username,password_hash) VALUES(?,?)');$s->execute([$username,password_hash($password,PASSWORD_DEFAULT)]);$id=(int)$db->lastInsertId();
      $s=$db->prepare('INSERT INTO admin_user_groups(user_id,group_id) SELECT ?,id FROM admin_groups WHERE id=?');
      foreach(array_unique($groups) as $gid)$s->execute([$id,$gid]);$db->commit();
     }catch(Throwable $e){if($db->inTransaction())$db->rollBack();http_response_code(422);exit('Impossible de créer cet utilisateur (identifiant déjà utilisé ou groupe invalide).');}
    }elseif($action==='toggle'){
     $id=(int)($_POST['id']??0);if($id===(int)AdminAuth::user()['id']){http_response_code(422);exit('Impossible de désactiver son propre compte');}
     $s=$db->prepare('UPDATE admin_users SET active=1-active WHERE id=?');$s->execute([$id]);
    }else{http_response_code(400);exit('Action invalide');}
    self::go('/admin/utilisateurs');
   }
   $users=$db->query("SELECT u.id,u.username,u.active,GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', ') groups_list FROM admin_users u LEFT JOIN admin_user_groups ug ON ug.user_id=u.id LEFT JOIN admin_groups g ON g.id=ug.group_id GROUP BY u.id,u.username,u.active ORDER BY u.id")->fetchAll();
   $groups=$db->query('SELECT id,name FROM admin_groups ORDER BY name')->fetchAll();
   $body='<div class="tablewrap"><table><tr><th>Identifiant</th><th>Groupes</th><th>État</th><th>Action</th></tr>';
   foreach($users as $u)$body.='<tr><td>'.self::esc($u['username']).'</td><td>'.self::esc($u['groups_list']).'</td><td>'.($u['active']?'Actif':'Inactif').'</td><td><form method="post">'.self::token().'<input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="'.(int)$u['id'].'"><button>'.($u['active']?'Désactiver':'Activer').'</button></form></td></tr>';
   $body.='</table></div><h2>Créer un utilisateur</h2><form method="post" class="panel">'.self::token().'<input type="hidden" name="action" value="create"><label>Identifiant<input name="username" required minlength="3"></label><label>Mot de passe initial<input name="password" type="password" minlength="12" required></label><fieldset><legend>Groupes</legend>';
   foreach($groups as $g)$body.='<label class="check"><input type="checkbox" name="groups[]" value="'.(int)$g['id'].'"> '.self::esc($g['name']).'</label>';
   self::page('Utilisateurs',$body.'</fieldset><button>Créer le compte</button></form>');
  }
  if($path==='/admin/groupes'){
   AdminAuth::require('groups.manage');$db=ssf_db();
   if($method==='POST'){
    AdminAuth::checkCsrf();$action=(string)($_POST['action']??'');
    if($action==='create'){$name=trim((string)($_POST['name']??''));if(mb_strlen($name)<3||mb_strlen($name)>100){http_response_code(422);exit('Nom invalide');}$s=$db->prepare('INSERT INTO admin_groups(name) VALUES(?)');$s->execute([$name]);}
    elseif($action==='permissions'){
     $gid=(int)($_POST['group_id']??0);$s=$db->prepare('SELECT is_system,name FROM admin_groups WHERE id=?');$s->execute([$gid]);$g=$s->fetch();
     if(!$g||$g['name']==='Administrateurs'){http_response_code(403);exit('Le groupe Administrateurs est protégé');}
     $ids=array_unique(array_map('intval',(array)($_POST['permissions']??[])));
     $db->beginTransaction();try{$s=$db->prepare('DELETE FROM admin_group_permissions WHERE group_id=?');$s->execute([$gid]);
      $s=$db->prepare('INSERT INTO admin_group_permissions(group_id,permission_id) SELECT ?,id FROM admin_permissions WHERE id=?');
      foreach($ids as $pid)$s->execute([$gid,$pid]);$db->commit();
     }catch(Throwable $e){$db->rollBack();throw $e;}
    }else{http_response_code(400);exit('Action invalide');}
    self::go('/admin/groupes');
   }
   $groups=$db->query('SELECT id,name,description FROM admin_groups ORDER BY id')->fetchAll();
   $perms=$db->query('SELECT id,code,label FROM admin_permissions ORDER BY id')->fetchAll();
   $assigned=$db->query('SELECT group_id,permission_id FROM admin_group_permissions')->fetchAll();$map=[];
   foreach($assigned as $a)$map[(int)$a['group_id']][(int)$a['permission_id']]=true;
   $body='<p>Les permissions du groupe Administrateurs sont protégées.</p>';
   foreach($groups as $g){$locked=$g['name']==='Administrateurs';$body.='<form method="post" class="panel"><h2>'.self::esc($g['name']).'</h2>'.self::token().'<input type="hidden" name="action" value="permissions"><input type="hidden" name="group_id" value="'.(int)$g['id'].'">';
    foreach($perms as $p)$body.='<label class="check"><input type="checkbox" name="permissions[]" value="'.(int)$p['id'].'" '.(!empty($map[(int)$g['id']][(int)$p['id']])?'checked ':'').($locked?'disabled':'').'> '.self::esc($p['label']).' <small>('.self::esc($p['code']).')</small></label>';
    if(!$locked)$body.='<button>Enregistrer les permissions</button>';$body.='</form>';
   }
   self::page('Groupes et permissions',$body.'<h2>Créer un groupe</h2><form method="post" class="panel">'.self::token().'<input type="hidden" name="action" value="create"><label>Nom du groupe<input name="name" maxlength="100" required></label><button>Créer</button></form>');
  }
  http_response_code(404);exit('Page introuvable');
 }
 private static function loginForm(): string {
  return '<form method="post" class="panel login">'.self::token().'<label>Identifiant<input name="username" autocomplete="username" required></label><label>Mot de passe<input name="password" type="password" autocomplete="current-password" required></label><button>Se connecter</button></form>';
 }
}
