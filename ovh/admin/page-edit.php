<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$pageKey = (string) ($_GET['page_key'] ?? '');

if ($pageKey === '') {
    cms_flash('error', 'Page principale introuvable.');
    cms_redirect('/admin/pages');
}

$errors = [];
$page = cms_main_page($pageKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();
    $payload = cms_page_payload_from_request('main', $pageKey);
    $errors = cms_validate_page_payload($payload);

    if ($errors === []) {
        try {
            cms_save_page($payload, isset($page['id']) ? (int) $page['id'] : null);
            cms_flash('success', 'La page principale a été enregistrée.');
            cms_redirect('/admin/page-edit?page_key=' . urlencode($pageKey));
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    $page = array_merge($page, $payload);
}

cms_render_admin_start('Modifier une page principale', '/admin/pages');
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>
<?php cms_render_page_form($page, 'main', 'Modifier la page ' . (string) $page['title']); ?>
<?php cms_render_admin_end(); ?>