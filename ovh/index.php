<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

try {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = cms_base_url();

    if ($base !== '' && str_starts_with($requestPath, $base)) {
        $requestPath = substr($requestPath, strlen($base)) ?: '/';
    }

    $settings = cms_settings();

    if ($requestPath === '/blog' || $requestPath === '/blog/') {
        cms_render_blog_index_page($settings);
        exit;
    }

    if ($requestPath === '/histoire' || $requestPath === '/histoire/') {
        cms_render_histoire_page($settings);
        exit;
    }

    if ($requestPath === '/avis' || $requestPath === '/avis/') {
        cms_render_avis_page($settings);
        exit;
    }

    if ($requestPath === '/prestations' || $requestPath === '/prestations/') {
        cms_render_prestations_page($settings);
        exit;
    }

    if ($requestPath === '/estimation-en-ligne/confirmation' || $requestPath === '/estimation-immobiliere-auxois-morvan/confirmation') {
      cms_render_estimation_confirmation_page($settings);
      exit;
    }

    if ($requestPath === '/estimation-en-ligne' || $requestPath === '/estimation-en-ligne/' || $requestPath === '/estimation-immobiliere-auxois-morvan' || $requestPath === '/estimation-immobiliere-auxois-morvan/') {
      $formState = ['errors' => [], 'payload' => []];

      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        cms_require_csrf();
        $formState = cms_handle_estimation_request($settings);

        if (($formState['errors'] ?? []) === []) {
          cms_redirect('/estimation-en-ligne/confirmation');
        }
      }

      cms_render_estimation_tunnel_page($settings, (array) ($formState['payload'] ?? []), (array) ($formState['errors'] ?? []));
      exit;
    }

    if (preg_match('#^/blog/([^/]+)/?$#', $requestPath, $matches) === 1) {
        $post = cms_public_blog_post((string) ($matches[1] ?? ''));

        if (!$post) {
            http_response_code(404);
            ?><!doctype html>
            <html lang="fr">
              <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Article introuvable</title>
                <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/site.css')) ?>">
              </head>
              <body>
                <main class="shell not-found">
                  <p class="eyebrow">404</p>
                  <h1>Article introuvable</h1>
                  <p>Le contenu demandé n’existe pas encore ou n’est pas publié.</p>
                  <a class="button primary" href="<?= cms_h(cms_url('/blog')) ?>">Retour au blog</a>
                </main>
              </body>
            </html><?php
            exit;
        }

        cms_render_blog_post_page($post, $settings);
        exit;
    }

    $page = cms_public_page_by_path($requestPath);

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