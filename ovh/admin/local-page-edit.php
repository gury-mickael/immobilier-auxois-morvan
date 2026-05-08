<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$errors = [];
$page = cms_local_page($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();

    if (($_POST['action'] ?? '') === 'delete' && $id !== null) {
        cms_delete_local_page($id);
        cms_flash('success', 'La page locale a été supprimée.');
        cms_redirect('/admin/local-pages');
    }

    $payload = cms_page_payload_from_request('local', null);
    $errors = cms_validate_page_payload($payload);

    if ($errors === []) {
        try {
            $savedId = cms_save_page($payload, $id);
            cms_flash('success', 'La page locale a été enregistrée.');
            cms_redirect('/admin/local-page-edit?id=' . $savedId);
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    $page = array_merge($page, $payload);
}

cms_render_admin_start('Modifier une page locale', '/admin/local-pages');
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>
<?php cms_render_page_form($page, 'local', $id ? 'Modifier la page locale' : 'Créer une page locale'); ?>
<?php if ($id): ?>
  <form method="post" onsubmit="return confirm('Supprimer cette page locale ?');">
    <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
    <input type="hidden" name="action" value="delete">
    <button class="danger-button" type="submit">Supprimer cette page locale</button>
  </form>
<?php endif; ?>
<?php cms_render_admin_end(); ?>