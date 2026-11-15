<?php
declare(strict_types=1);
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ini_set('session.cookie_secure','1');
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
require dirname(__DIR__).'/src/bootstrap.php';
require dirname(__DIR__).'/src/Pricing.php';
require dirname(__DIR__).'/src/RegistrationRepository.php';
require dirname(__DIR__).'/src/RegistrationService.php';
require dirname(__DIR__).'/src/RegistrationController.php';
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
$method=$_SERVER['REQUEST_METHOD']??'GET';
if ($path==='/admin' || str_starts_with((string)$path,'/admin/')) {
 require dirname(__DIR__).'/src/AdminAuth.php';
 require dirname(__DIR__).'/src/AdminExport.php';
 require dirname(__DIR__).'/src/AdminController.php';
 AdminController::route((string)$path,$method);
}
if ($path==='/health' && $method==='GET') { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['status'=>'ok']); exit; }
if ($path==='/inscription' && $method==='POST') (new RegistrationController())->submit();
if ($path==='/merci' && $method==='GET') { require dirname(__DIR__).'/views/thanks.php'; exit; }
if ($path==='/confidentialite' && $method==='GET') { require dirname(__DIR__).'/views/privacy.php'; exit; }
if ($path==='/' && $method==='GET') {
 $_SESSION['csrf']??=bin2hex(random_bytes(32));
 require dirname(__DIR__).'/views/home.php'; exit;
}
http_response_code($path==='/inscription'?405:404);
header('Content-Type: text/plain; charset=utf-8');
echo $path==='/inscription'?'Méthode non autorisée':'Page introuvable';
