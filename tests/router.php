<?php
declare(strict_types=1);
// Routeur uniquement pour le serveur de développement intégré à PHP.
// Les ressources statiques existantes sont servies directement par PHP.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($path) && $path !== '/') {
    $root = realpath(__DIR__ . '/../public');
    $file = realpath($root . '/' . ltrim($path, '/'));
    if ($root !== false && $file !== false && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)) {
        return false;
    }
}
require __DIR__ . '/../public/index.php';
