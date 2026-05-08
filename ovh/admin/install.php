<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$token = $_GET['token'] ?? null;

if (!cms_install_token_is_valid(is_string($token) ? $token : null)) {
    http_response_code(403);
    exit('Accès refusé.');
}

$installDirectory = dirname(__DIR__) . '/install';
$messages = [];

try {
    cms_run_sql_file($installDirectory . '/schema.sql');
    $messages[] = 'Schema SQL importe.';

    cms_run_sql_file($installDirectory . '/seed.sql');
    $messages[] = 'Contenu initial importe.';
} catch (Throwable $exception) {
    http_response_code(500);
    ?><!doctype html>
    <html lang="fr">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Erreur d'installation</title>
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/admin.css')) ?>">
      </head>
      <body class="login-page">
        <section class="login-card">
          <p class="eyebrow">Installation OVH</p>
          <h1>Import échoué</h1>
          <div class="flash flash-error"><?= cms_h($exception->getMessage()) ?></div>
        </section>
      </body>
    </html><?php
    exit;
}
?><!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation OVH</title>
    <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/admin.css')) ?>">
  </head>
  <body class="login-page">
    <section class="login-card">
      <p class="eyebrow">Installation OVH</p>
      <h1>Import terminé</h1>
      <?php foreach ($messages as $message): ?>
        <div class="flash flash-success"><?= cms_h($message) ?></div>
      <?php endforeach; ?>
      <p>Tu peux maintenant ouvrir l'admin puis supprimer ce script et le dossier install après validation.</p>
      <a class="primary-button" href="<?= cms_h(cms_url('/admin/login')) ?>">Ouvrir l'admin</a>
    </section>
  </body>
</html>