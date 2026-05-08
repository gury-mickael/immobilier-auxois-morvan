<?php
// Routeur pour `php -S` qui simule le rewrite Apache de la prod OVH.
// Sert les fichiers statiques tels quels et délègue tout le reste à index.php.

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Empêche d'exposer le dossier app/
if (str_starts_with($path, '/app/')) {
    http_response_code(404);
    return true;
}

$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false; // laisse le serveur built-in servir le fichier
}

require __DIR__ . '/index.php';
