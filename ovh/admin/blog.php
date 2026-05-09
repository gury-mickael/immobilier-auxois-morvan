<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$settings = cms_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();
    cms_save_blog_visibility(isset($_POST['blog_enabled']));
    cms_flash('success', isset($_POST['blog_enabled']) ? 'Le blog est maintenant visible sur le site public.' : 'Le blog est maintenant masqué sur le site public.');
    cms_redirect('/admin/blog');
}

$blogEnabled = cms_is_blog_public_enabled($settings);
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

  <form method="post" class="blog-visibility-card">
    <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
    <label class="toggle-field blog-visibility-toggle">
      <span>
        <strong>Afficher le blog sur le site public</strong>
        <small><?= $blogEnabled ? 'Le menu public, la page d’accueil et les URLs du blog sont visibles.' : 'Le blog est masqué du menu, de la home et des pages publiques.' ?></small>
      </span>
      <input type="checkbox" name="blog_enabled" value="1"<?= $blogEnabled ? ' checked' : '' ?>>
    </label>
    <div class="blog-visibility-actions">
      <span class="status-badge status-<?= $blogEnabled ? 'published' : 'draft' ?>"><?= $blogEnabled ? 'Visible' : 'Masqué' ?></span>
      <button class="secondary-button" type="submit">Enregistrer l’affichage</button>
    </div>
  </form>

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