<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$pages = cms_local_pages();

cms_render_admin_start('Pages locales', '/admin/local-pages');
?>
<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">SEO local</p>
      <h1>Pages locales</h1>
    </div>
    <a class="primary-button" href="<?= cms_h(cms_url('/admin/local-page-edit')) ?>">Nouvelle page locale</a>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Ville</th>
          <th>Titre</th>
          <th>Slug</th>
          <th>Statut</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pages as $page): ?>
          <tr>
            <td><?= cms_h((string) $page['city']) ?></td>
            <td><?= cms_h((string) $page['title']) ?></td>
            <td><?= cms_h((string) $page['slug']) ?></td>
            <td><span class="status-badge status-<?= cms_h((string) $page['status']) ?>"><?= cms_h((string) $page['status']) ?></span></td>
            <td><a class="secondary-button" href="<?= cms_h(cms_url('/admin/local-page-edit?id=' . (int) $page['id'])) ?>">Modifier</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php cms_render_admin_end(); ?>