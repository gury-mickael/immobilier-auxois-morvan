<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

try {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = cms_base_url();

    if ($base !== '' && str_starts_with($requestPath, $base)) {
        $requestPath = substr($requestPath, strlen($base)) ?: '/';
    }

    $page = cms_public_page_by_path($requestPath);
    $settings = cms_settings();

    if (!$page) {
        http_response_code(404);
        ?><!doctype html>
        <html lang="fr">
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Page introuvable</title>
            <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/site.css')) ?>">
          </head>
          <body>
            <main class="shell not-found">
              <p class="eyebrow">404</p>
              <h1>Page introuvable</h1>
              <p>Le contenu demandé n’existe pas encore ou n’est pas publié.</p>
              <a class="button primary" href="<?= cms_h(cms_url('/')) ?>">Retour à l’accueil</a>
            </main>
          </body>
        </html><?php
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($page['page_key'] ?? null) === 'contact') {
      $errors = cms_handle_contact_request($settings);

      if ($errors === []) {
        cms_redirect('/contact?merci=1');
      }

      $page['_contact_errors'] = $errors;
    }

    cms_render_public_page($page, $settings);
} catch (Throwable $exception) {
    http_response_code(500);
    ?><!doctype html>
    <html lang="fr">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Erreur CMS</title>
        <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/site.css')) ?>">
      </head>
      <body>
        <main class="shell not-found">
          <p class="eyebrow">Configuration</p>
          <h1>Le CMS OVH n’est pas encore prêt.</h1>
          <p>Vérifie le fichier .env OVH, l’import SQL et les droits du dossier d’upload.</p>
          <?php if (cms_config()['app_env'] !== 'production'): ?>
            <pre><?= cms_h($exception->getMessage()) ?></pre>
          <?php endif; ?>
        </main>
      </body>
    </html><?php
}