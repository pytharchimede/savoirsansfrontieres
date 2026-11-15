<?php
declare(strict_types=1);
session_start();
require dirname(__DIR__).'/src/bootstrap.php';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/health') { header('Content-Type: application/json'); echo json_encode(['status'=>'ok']); exit; }
if ($path === '/inscription' && $_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Session expirée'); }
 if (!empty($_POST['website'])) { http_response_code(400); exit; }
 $type = $_POST['type'] ?? '';
 $name = trim((string)($_POST['name'] ?? ''));
 $email = trim((string)($_POST['email'] ?? ''));
 $phone = trim((string)($_POST['phone'] ?? ''));
 if (!in_array($type,['etudiant','enseignant'],true) || mb_strlen($name)<3 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($phone)<6 || empty($_POST['consent'])) { http_response_code(422); exit('Informations invalides'); }
 try {
 $db = ssf_db();
 $stmt = $db->prepare('INSERT INTO registrations (type, full_name, email, phone, created_at) VALUES (?, ?, ?, ?, NOW())');
 $stmt->execute([$type,$name,$email,$phone]);
 header('Location: /merci',true,303); exit;
 } catch (Throwable $e) { error_log($e->getMessage()); http_response_code(503); exit('Service momentanément indisponible'); }
}
if ($path === '/merci') { echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Merci</title><body><h1>Merci pour votre inscription !</h1><a href="/">Retour à l’accueil</a></body></html>'; exit; }
if ($path !== '/') { http_response_code(404); exit('Page introuvable'); }
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
require dirname(__DIR__).'/views/home.php';
