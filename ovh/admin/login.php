<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (cms_current_admin()) {
    cms_redirect('/admin');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();
    $user = cms_attempt_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));

    if ($user) {
        cms_login($user);
        cms_flash('success', 'Connexion réussie.');
        cms_redirect('/admin');
    }

    $error = 'Email ou mot de passe incorrect.';
}
?><!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Connexion admin</title>
    <link rel="stylesheet" href="<?= cms_h(cms_url('/assets/admin.css')) ?>">
  </head>
  <body class="login-page">
    <section class="login-card">
      <p class="eyebrow">CMS maison</p>
      <h1>Connexion admin</h1>
      <p>Accès privé par email et mot de passe pour Mickael et Marion.</p>
      <?php if ($error): ?>
        <div class="flash flash-error"><?= cms_h($error) ?></div>
      <?php endif; ?>
      <form method="post" class="admin-form-stack compact-form">
        <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
        <label>
          Email
          <input type="email" name="email" autocomplete="email" required>
        </label>
        <label>
          Mot de passe
          <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="primary-button" type="submit">Se connecter</button>
      </form>
    </section>
  </body>
</html>