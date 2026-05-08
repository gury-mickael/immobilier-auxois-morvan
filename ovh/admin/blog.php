<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$posts = cms_blog_posts();

cms_render_admin_start('Blog', '/admin/blog');
?>
<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">Blog</p>
      <h1>Articles</h1>
      <p class="lead">Le blog peut maintenant être piloté depuis l'admin OVH, avec image de couverture, contenu riche et publication sans redéploiement.</p>
    </div>
    <a class="primary-button" href="<?= cms_h(cms_url('/admin/blog-edit')) ?>">Nouvel article</a>
  </div>

  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Titre</th>
          <th>Catégorie</th>
          <th>Slug</th>
          <th>Statut</th>
          <th>Source</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $post): ?>
          <tr>
            <td>
              <strong><?= cms_h((string) $post['title']) ?></strong>
              <div class="lead"><?= cms_h((string) $post['excerpt']) ?></div>
            </td>
            <td><?= cms_h((string) $post['category']) ?></td>
            <td>/blog/<?= cms_h((string) $post['slug']) ?></td>
            <td><span class="status-badge status-<?= cms_h((string) $post['status']) ?>"><?= cms_h((string) $post['status']) ?></span></td>
            <td><?= cms_h((string) ($post['source'] === 'file' ? 'fichier' : 'base')) ?></td>
            <td><a class="secondary-button" href="<?= cms_h(cms_url('/admin/blog-edit?slug=' . urlencode((string) $post['slug']))) ?>">Modifier</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php cms_render_admin_end(); ?>