<?php
declare(strict_types=1);
final class AdminController {
 private static function esc(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
 private static function go(string $path): never {header('Location: '.$path,true,303);exit;}
 private static function page(string $title,string $body): never {
  $user=AdminAuth::user();$name=self::esc($user['username']??'');
  header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store');
  $head='<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.self::esc($title).' — SSF Administration</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer"><link rel="stylesheet" href="/assets/admin.css?v=20261115-sidebar1"></head><body>';
  if(!$user){echo $head.'<div class="login-screen"><div class="login-shell"><div class="login-brand"><i class="fa-solid fa-graduation-cap"></i><span>SAVOIR SANS FRONTIÈRES<br><small>Espace administration</small></span></div><div class="page-kicker">Accès sécurisé</div><h1>'.self::esc($title).'</h1>'.$body.'</div></div></body></html>';exit;}
  $path=(string)(parse_url($_SERVER['REQUEST_URI']??'/admin',PHP_URL_PATH)??'/admin');
  $items=[['/admin','Tableau de bord','fa-chart-pie','registrations.view'],['/admin/inscriptions','Inscriptions','fa-address-card','registrations.view'],['/admin/referentiels','Matières et niveaux','fa-book-open','catalogs.manage'],['/admin/tarifs','Tarifs','fa-euro-sign','pricing.manage'],['/admin/utilisateurs','Utilisateurs','fa-users','users.manage'],['/admin/groupes','Groupes et accès','fa-shield-halved','groups.manage']];
  $nav='';foreach($items as [$url,$label,$icon,$permission]){if(!AdminAuth::can($permission))continue;$active=$path===$url?' active':'';$current=$active?' aria-current="page"':'';$nav.='<a class="'.$active.'" href="'.$url.'"'.$current.'><i class="fa-solid '.$icon.'" aria-hidden="true"></i><span>'.self::esc($label).'</span></a>';}
  echo $head.'<div class="admin-shell"><aside class="sidebar" id="admin-sidebar" data-sidebar><a class="sidebar-brand" href="/admin"><span class="brand-icon"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i></span><span class="brand-name">SAVOIR SANS FRONTIÈRES<small>ESPACE ADMINISTRATION</small></span></a><div class="sidebar-label">Navigation</div><nav class="sidebar-nav" aria-label="Navigation principale">'.$nav.'</nav><div class="sidebar-bottom"><div class="sidebar-user"><i class="fa-solid fa-circle-user" aria-hidden="true"></i><span>'. $name .'</span></div><form action="/admin/logout" method="post">'.self::token().'<button type="submit"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>Déconnexion</button></form></div></aside><div class="backdrop" data-backdrop></div><div class="workspace"><header class="topbar"><button class="menu-toggle" type="button" data-menu-toggle aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="admin-sidebar"><i class="fa-solid fa-bars"></i></button><span class="topbar-caption">Savoir Sans Frontières / Administration</span><div class="topbar-right"><a href="/" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square"></i> Voir le site</a><span class="topbar-avatar"><i class="fa-solid fa-user"></i></span></div></header><main class="content"><div class="page-heading"><div><div class="page-kicker">Espace de gestion</div><h1>'.self::esc($title).'</h1></div></div>'.$body.'</main></div></div><script defer src="/assets/admin.js?v=20261115-sidebar1"></script></body></html>';exit;
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
   $links='<div class="quick-links"><a class="feature-tile" href="/admin/inscriptions"><i class="fa-solid fa-address-card"></i>Consulter les inscriptions</a>';
   if(AdminAuth::can('pricing.manage'))$links.='<a class="feature-tile" href="/admin/tarifs"><i class="fa-solid fa-euro-sign"></i>Gérer les tarifs</a>';
   if(AdminAuth::can('catalogs.manage'))$links.='<a class="feature-tile" href="/admin/referentiels"><i class="fa-solid fa-book-open"></i>Matières et niveaux</a>';
   $links.='</div>';
   self::page('Tableau de bord','<div class="metrics"><article><i class="fa-solid fa-users"></i><span>Total des inscrits</span><strong>'.$total.'</strong></article><article><i class="fa-solid fa-graduation-cap"></i><span>Apprenants</span><strong>'.$learn.'</strong></article><article><i class="fa-solid fa-chalkboard-user"></i><span>Enseignants</span><strong>'.$teach.'</strong></article></div><h2>Accès rapides</h2>'.$links);
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
    $stmt=$db->prepare('SELECT r.id,r.type,r.full_name,r.email,r.phone,COALESCE(s.label,r.subject) subject,COALESCE(l.label,r.student_level) student_level,r.created_at FROM registrations r LEFT JOIN education_subjects s ON s.code COLLATE utf8mb4_unicode_ci = r.subject COLLATE utf8mb4_unicode_ci LEFT JOIN education_levels l ON l.code COLLATE utf8mb4_unicode_ci = r.student_level COLLATE utf8mb4_unicode_ci'.$filter.' ORDER BY r.created_at DESC,r.id DESC');
    $stmt->execute($params);$rows=$stmt->fetchAll();
    if($export==='xlsx')AdminExport::xlsx($rows);AdminExport::pdf($rows);
   }
   $stmt=$db->prepare('SELECT r.id,r.type,r.full_name,r.email,r.phone,COALESCE(s.label,r.subject) subject,COALESCE(l.label,r.student_level) student_level,r.created_at FROM registrations r LEFT JOIN education_subjects s ON s.code COLLATE utf8mb4_unicode_ci = r.subject COLLATE utf8mb4_unicode_ci LEFT JOIN education_levels l ON l.code COLLATE utf8mb4_unicode_ci = r.student_level COLLATE utf8mb4_unicode_ci'.$filter.' ORDER BY r.created_at DESC,r.id DESC LIMIT 30 OFFSET '.$offset);
   $stmt->execute($params);$rows=$stmt->fetchAll();
   $body='<form method="get" class="filters"><input name="q" placeholder="Nom, email, téléphone" value="'.self::esc($q).'"><select name="type"><option value="">Tous les profils</option><option value="etudiant"'.($type==='etudiant'?' selected':'').'>Apprenants</option><option value="enseignant"'.($type==='enseignant'?' selected':'').'>Enseignants</option></select><button>Filtrer</button></form><p>'.$total.' inscription(s)</p>';
   $query=http_build_query(['q'=>$q,'type'=>$type]);
   if(AdminAuth::can('registrations.export'))$body.='<p><a class="btn" href="/admin/inscriptions?'.$query.'&export=xlsx">Exporter Excel (.xlsx)</a> <a class="btn secondary" href="/admin/inscriptions?'.$query.'&export=pdf">Exporter PDF</a></p>';
   $body.='<div class="records">';
   foreach($rows as $r){
    $body.='<article class="record-card"><div class="record-top"><h3>'.self::esc($r['full_name']).'</h3><span class="status">'.($r['type']==='etudiant'?'Apprenant':'Enseignant').'</span></div><dl class="record-meta">';
    foreach(['email'=>'Email','phone'=>'Téléphone','subject'=>'Matière','student_level'=>'Niveau','created_at'=>'Inscrit le'] as $key=>$label){
     if(($r[$key]??'')==='')continue;
     $body.='<div><dt>'.$label.'</dt><dd>'.self::esc($r[$key]).'</dd></div>';
    }
    $body.='</dl></article>';
   }
   if(!$rows)$body.='<div class="empty-state"><i class="fa-solid fa-inbox"></i>Aucune inscription pour ces critères.</div>';
   $body.='</div><div class="pagination">';
   if($page>1)$body.='<a href="?'.$query.'&page='.($page-1).'"><i class="fa-solid fa-arrow-left"></i> Précédent</a> ';
   $body.='<span>Page '.$page.' / '.max(1,(int)ceil($total/$perPage)).'</span>';
   if($offset+$perPage<$total)$body.='<a href="?'.$query.'&page='.($page+1).'">Suivant <i class="fa-solid fa-arrow-right"></i></a>';
   self::page('Inscriptions',$body.'</div>');
  }

  if($path==='/admin/tarifs'){
   AdminAuth::require('pricing.manage');$db=ssf_db();
   if($method==='POST'){
    AdminAuth::checkCsrf();
    $values=[];
    foreach(['hourly','monthly'] as $code){
     $raw=trim((string)($_POST[$code]??''));
     if(!preg_match('/^(?:0|[1-9][0-9]{0,5})(?:[.,][0-9]{1,2})?$/D',$raw)){
      http_response_code(422);exit('Montant invalide : utilisez un montant positif en euros, avec deux décimales maximum.');
     }
     $parts=explode('.',str_replace(',','.',$raw),2);
     $cents=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
     if($cents<1||$cents>99999999){http_response_code(422);exit('Montant hors limites');}
     $values[$code]=$cents;
    }
    $db->beginTransaction();
    try{
     $stmt=$db->prepare('UPDATE site_pricing SET amount_cents=? WHERE code=?');
     foreach($values as $code=>$amount){$stmt->execute([$amount,$code]);if($stmt->rowCount()===0){
      $check=$db->prepare('SELECT 1 FROM site_pricing WHERE code=?');$check->execute([$code]);
      if(!$check->fetchColumn()){$ins=$db->prepare('INSERT INTO site_pricing(code,amount_cents) VALUES(?,?)');$ins->execute([$code,$amount]);}
     }}
     $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    self::go('/admin/tarifs?updated=1');
   }
   if($method!=='GET'){http_response_code(405);exit('Méthode non autorisée');}
   $prices=ssf_pricing();
   $body='<p>Modifiez les tarifs affichés sur la page d’accueil. Les montants sont exprimés en euros (€) et enregistrés immédiatement.</p>';
   if(isset($_GET['updated']))$body.='<p class="notice">Tarifs enregistrés avec succès.</p>';
   $body.='<form method="post" class="panel">'.self::token()
    .'<label>Tarif horaire (€)<input name="hourly" type="number" min="0.01" max="999999.99" step="0.01" inputmode="decimal" required value="'.self::esc(number_format($prices['hourly']/100,2,'.','')).'"></label>'
    .'<label>Forfait mensuel — 8 heures (€)<input name="monthly" type="number" min="0.01" max="999999.99" step="0.01" inputmode="decimal" required value="'.self::esc(number_format($prices['monthly']/100,2,'.','')).'"></label>'
    .'<p>Le tarif horaire indicatif du forfait mensuel est calculé automatiquement (forfait ÷ 8).</p>'
    .'<button type="submit">Enregistrer les tarifs</button></form>';
   self::page('Gestion des tarifs',$body);
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
    $body.='<h2>'.self::esc($title).'</h2><div class="records">';
    foreach($rows as $item){
     $body.='<article class="record-card"><div class="record-top"><h3>'.self::esc($item['label']).'</h3><span class="status '.($item['active']?'active':'').'">'.($item['active']?'Actif':'Inactif').'</span></div><form method="post">'.self::token().'<input type="hidden" name="kind" value="'.$kind.'"><input type="hidden" name="action" value="rename"><input type="hidden" name="id" value="'.(int)$item['id'].'"><label>Libellé<input name="label" maxlength="120" required value="'.self::esc($item['label']).'"></label><div class="record-actions"><button>Enregistrer</button></div></form><div class="record-actions"><form method="post">'.self::token().'<input type="hidden" name="kind" value="'.$kind.'"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="'.(int)$item['id'].'"><button>'.($item['active']?'Désactiver':'Activer').'</button></form></div></article>';
    }
    $body.='</div><form method="post" class="panel">'.self::token().'<input type="hidden" name="kind" value="'.$kind.'"><input type="hidden" name="action" value="create"><label>Ajouter un élément<input name="label" maxlength="120" required></label><button>Ajouter</button></form>';
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
   $body='<div class="records">';
   foreach($users as $u)$body.='<article class="record-card"><div class="record-top"><h3><i class="fa-solid fa-circle-user"></i> '.self::esc($u['username']).'</h3><span class="status '.($u['active']?'active':'').'">'.($u['active']?'Actif':'Inactif').'</span></div><dl class="record-meta"><div><dt>Groupes</dt><dd>'.self::esc($u['groups_list']).'</dd></div></dl><div class="record-actions"><form method="post">'.self::token().'<input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="'.(int)$u['id'].'"><button>'.($u['active']?'Désactiver':'Activer').'</button></form></div></article>';
   $body.='</div><h2>Créer un utilisateur</h2><form method="post" class="panel">'.self::token().'<input type="hidden" name="action" value="create"><label>Identifiant<input name="username" required minlength="3"></label><label>Mot de passe initial<input name="password" type="password" minlength="12" required></label><fieldset><legend>Groupes</legend>';
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
