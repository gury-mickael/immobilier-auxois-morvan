<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$pages = cms_main_pages();

cms_render_admin_start('Pages principales', '/admin/pages');
?>
<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">Pages</p>
      <h1>Pages principales</h1>
    </div>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Clé</th>
          <th>Titre</th>
          <th>Slug</th>
          <th>Statut</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pages as $page): ?>
          <tr>
            <td><?= cms_h((string) $page['page_key']) ?></td>
            <td><?= cms_h((string) $page['title']) ?></td>
            <td><?= cms_h((string) $page['slug']) ?></td>
            <td><span class="status-badge status-<?= cms_h((string) ($page['status'] ?? 'draft')) ?>"><?= cms_h((string) ($page['status'] ?? 'draft')) ?></span></td>
            <td><a class="secondary-button" href="<?= cms_h(cms_url('/admin/page-edit?page_key=' . urlencode((string) $page['page_key']))) ?>">Modifier</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php cms_render_admin_end(); ?>