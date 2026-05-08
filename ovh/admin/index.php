<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$admin = cms_require_admin();
$mainPages = cms_main_pages();
$localPages = cms_local_pages();
$mediaItems = cms_media_items();
$users = cms_admin_users();

cms_render_admin_start('Tableau de bord', '/admin');
?>
<section class="panel">
  <div class="panel-head">
    <div>
      <p class="eyebrow">Bonjour</p>
      <h1><?= cms_h($admin['name']) ?></h1>
    </div>
    <a class="secondary-button" href="<?= cms_h(cms_url('/')) ?>" target="_blank" rel="noreferrer">Voir le site</a>
  </div>
  <p class="lead">Ce mini-CMS maison fonctionne sur PHP + MySQL et protège toute la zone admin par session serveur.</p>
</section>

<section class="stats-row">
  <article class="stat-panel"><strong><?= count($mainPages) ?></strong><span>pages principales</span></article>
  <article class="stat-panel"><strong><?= count($localPages) ?></strong><span>pages locales</span></article>
  <article class="stat-panel"><strong><?= count($mediaItems) ?></strong><span>images</span></article>
  <article class="stat-panel"><strong><?= count($users) ?></strong><span>comptes admin</span></article>
</section>

<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">Raccourcis</p>
      <h2>Actions prioritaires</h2>
    </div>
  </div>
  <div class="shortcut-grid">
    <a class="shortcut-card" href="<?= cms_h(cms_url('/admin/pages')) ?>">Modifier les pages principales</a>
    <a class="shortcut-card" href="<?= cms_h(cms_url('/admin/local-pages')) ?>">Gérer les pages locales</a>
    <a class="shortcut-card" href="<?= cms_h(cms_url('/admin/media')) ?>">Téléverser des images</a>
    <a class="shortcut-card" href="<?= cms_h(cms_url('/admin/users')) ?>">Gérer Mickael et Marion</a>
  </div>
</section>
<?php cms_render_admin_end(); ?>