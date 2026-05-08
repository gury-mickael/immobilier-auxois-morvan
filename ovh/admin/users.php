<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_require_admin();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$errors = [];
$editingUser = cms_admin_user($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_require_csrf();
    $payload = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'password' => (string) ($_POST['password'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($payload['name'] === '' || $payload['email'] === '') {
        $errors[] = 'Nom et email sont obligatoires.';
    }

    if ($errors === []) {
        try {
            $savedId = cms_save_admin_user($payload, $id);
            cms_flash('success', 'Le compte admin a été enregistré.');
            cms_redirect('/admin/users?id=' . $savedId);
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    $editingUser = array_merge($editingUser ?? [], $payload);
}

$users = cms_admin_users();

cms_render_admin_start('Utilisateurs', '/admin/users');
?>
<?php if ($errors): ?>
  <div class="flash flash-error"><?= cms_h(implode(' ', $errors)) ?></div>
<?php endif; ?>
<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">Comptes</p>
      <h1>Mickael et Marion</h1>
    </div>
    <?php if (count($users) < 2): ?>
      <a class="primary-button" href="<?= cms_h(cms_url('/admin/users')) ?>">Nouveau compte</a>
    <?php endif; ?>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>État</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?= cms_h((string) $user['name']) ?></td>
            <td><?= cms_h((string) $user['email']) ?></td>
            <td><span class="status-badge status-<?= (int) $user['is_active'] === 1 ? 'published' : 'draft' ?>"><?= (int) $user['is_active'] === 1 ? 'actif' : 'inactif' ?></span></td>
            <td><a class="secondary-button" href="<?= cms_h(cms_url('/admin/users?id=' . (int) $user['id'])) ?>">Modifier</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="panel">
  <div class="panel-head compact">
    <div>
      <p class="eyebrow">Formulaire</p>
      <h2><?= $editingUser ? 'Modifier le compte' : 'Créer un compte admin' ?></h2>
    </div>
  </div>
  <form method="post" class="admin-form-stack compact-form">
    <input type="hidden" name="_csrf" value="<?= cms_h(cms_csrf_token()) ?>">
    <label>
      Nom
      <input name="name" value="<?= cms_h((string) ($editingUser['name'] ?? '')) ?>" required>
    </label>
    <label>
      Email
      <input type="email" name="email" value="<?= cms_h((string) ($editingUser['email'] ?? '')) ?>" required>
    </label>
    <label>
      Mot de passe <?= $editingUser ? '(laisser vide pour conserver l’existant)' : '' ?>
      <input type="password" name="password" <?= $editingUser ? '' : 'required' ?>>
    </label>
    <label class="toggle-field">
      <span>Compte actif</span>
      <input type="checkbox" name="is_active" value="1" <?= !isset($editingUser['is_active']) || (int) $editingUser['is_active'] === 1 ? 'checked' : '' ?>>
    </label>
    <button class="primary-button" type="submit">Enregistrer</button>
  </form>
</section>
<?php cms_render_admin_end(); ?>