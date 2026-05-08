<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$slug = trim((string) ($_GET['slug'] ?? ''));
$errors = [];
$post = $slug !== '' ? cms_blog_post($slug) : cms_blank_blog_post();

if ($slug !== '' && !$post) {
    cms_flash('error', 'Article introuvable.');
    cms_redirect('/admin/blog');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();
    $payload = cms_blog_payload_from_request($slug !== '' ? $slug : null);
    $errors = cms_validate_blog_payload($payload);

    if ($errors === []) {
        try {
            $id = isset($post['id']) && $post['id'] !== null ? (int) $post['id'] : null;
            cms_save_blog_post($payload, $id);
            cms_flash('success', 'L\'article a été enregistré.');
            cms_redirect('/admin/blog-edit?slug=' . urlencode((string) $payload['slug']));
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    $post = array_merge($post ?? cms_blank_blog_post(), $payload);
}

cms_render_admin_start('Édition article', '/admin/blog');
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>
<?php cms_render_blog_form($post ?? cms_blank_blog_post(), !empty($post['slug']) ? 'Modifier l\'article' : 'Créer un article'); ?>
<?php cms_render_admin_end(); ?>