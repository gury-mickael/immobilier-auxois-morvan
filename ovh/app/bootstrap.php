<?php

declare(strict_types=1);

function cms_load_env(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $values;
}

function cms_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $root = dirname(__DIR__);
    $env = cms_load_env($root . '/.env');

    $get = static function (string $key, ?string $default = null) use ($env): ?string {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $env[$key] ?? $default;
        return $value === null ? null : trim($value);
    };

    $config = [
        'root' => $root,
        'app_env' => $get('APP_ENV', 'production'),
        'app_url' => rtrim((string) $get('APP_URL', ''), '/'),
        'db_host' => (string) $get('DB_HOST', '127.0.0.1'),
        'db_port' => (int) $get('DB_PORT', '3306'),
        'db_name' => (string) $get('DB_NAME', ''),
        'db_user' => (string) $get('DB_USER', ''),
        'db_password' => (string) $get('DB_PASSWORD', ''),
        'session_cookie_name' => (string) $get('SESSION_COOKIE_NAME', 'immobilier_auxois_admin'),
        'upload_dir' => trim((string) $get('UPLOAD_DIR', 'uploads/cms'), '/'),
        'upload_public_base' => '/' . trim((string) $get('UPLOAD_PUBLIC_BASE', '/uploads/cms'), '/'),
    ];

    return $config;
}

function cms_bootstrap_session(): void
{
    $config = cms_config();

    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name($config['session_cookie_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function cms_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = cms_config();
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_port'], $config['db_name']);

    $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function cms_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cms_base_url(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $directory = trim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($directory === '' || $directory === '.') {
        return '';
    }

    if (str_ends_with($directory, '/admin')) {
        return '/' . trim(substr($directory, 0, -6), '/');
    }

    return '/' . $directory;
}

function cms_url(string $path = '/'): string
{
    $base = cms_base_url();
    $normalized = '/' . ltrim($path, '/');
    return ($base === '' ? '' : $base) . ($normalized === '/' ? '/' : $normalized);
}

function cms_redirect(string $path): never
{
    header('Location: ' . cms_url($path));
    exit;
}

function cms_flash(string $type, string $message): void
{
    cms_bootstrap_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function cms_consume_flash(): ?array
{
    cms_bootstrap_session();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function cms_csrf_token(): string
{
    cms_bootstrap_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function cms_require_csrf(): void
{
    cms_bootstrap_session();
    $token = $_POST['_csrf'] ?? '';

    if (!is_string($token) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        exit('Jeton CSRF invalide.');
    }
}

function cms_default_settings(): array
{
    return [
        'site_name' => 'Immobilier Auxois Morvan',
        'baseline' => 'Mickael Gury et Marion Roulier accompagnent les projets immobiliers en Auxois et dans le Morvan.',
        'mickael_name' => 'Mickael Gury',
        'marion_name' => 'Marion Roulier',
        'phone' => '',
        'email' => '',
        'main_city' => 'Arnay-le-Duc',
        'covered_areas_json' => json_encode(['Arnay-le-Duc', 'Pouilly-en-Auxois', 'Autun', 'Saulieu', 'Beaune', 'Dijon'], JSON_UNESCAPED_UNICODE),
        'facebook_url' => '',
        'instagram_url' => '',
        'iad_url' => '',
        'footer_text' => 'Immobilier Auxois Morvan accompagne les projets immobiliers en Auxois et Morvan.',
        'main_cta_label' => 'Nous contacter',
        'main_cta_url' => '/contact',
    ];
}

function cms_settings(): array
{
    $defaults = cms_default_settings();
    $statement = cms_db()->query('SELECT * FROM cms_site_settings WHERE id = 1 LIMIT 1');
    $row = $statement->fetch();

    if (!$row) {
        return $defaults;
    }

    return array_merge($defaults, $row);
}

function cms_main_page_defaults(): array
{
    return [
        'accueil' => ['page_key' => 'accueil', 'page_type' => 'main', 'slug' => '/', 'title' => 'Accueil'],
        'vendre' => ['page_key' => 'vendre', 'page_type' => 'main', 'slug' => '/vendre', 'title' => 'Vendre votre bien'],
        'acheter' => ['page_key' => 'acheter', 'page_type' => 'main', 'slug' => '/acheter', 'title' => 'Acheter'],
        'estimation' => ['page_key' => 'estimation', 'page_type' => 'main', 'slug' => '/estimation', 'title' => 'Estimation'],
        'secteur' => ['page_key' => 'secteur', 'page_type' => 'main', 'slug' => '/secteur', 'title' => 'Secteur'],
        'fonds-de-commerce' => ['page_key' => 'fonds-de-commerce', 'page_type' => 'main', 'slug' => '/fonds', 'title' => 'Fonds de commerce'],
        'contact' => ['page_key' => 'contact', 'page_type' => 'main', 'slug' => '/contact', 'title' => 'Contact'],
    ];
}

function cms_blank_sections(): array
{
    return [[
        'eyebrow' => 'Bloc',
        'title' => 'Titre du bloc',
        'text' => '<p>Ajoutez ici votre contenu.</p>',
        'image' => '',
        'imageAlt' => '',
        'buttonLabel' => '',
        'buttonUrl' => '',
        'items' => [],
        'stats' => [],
    ]];
}

function cms_blank_page(array $seed = []): array
{
    return array_merge([
        'id' => null,
        'page_type' => 'main',
        'page_key' => null,
        'slug' => '/',
        'title' => '',
        'meta_description' => '',
        'is_indexable' => 1,
        'h1' => '',
        'hero_title' => '',
        'hero_subtitle' => '',
        'hero_image' => '',
        'hero_image_alt' => '',
        'intro_html' => '<p></p>',
        'sections_json' => json_encode(cms_blank_sections(), JSON_UNESCAPED_UNICODE),
        'cta_title' => '',
        'cta_text' => '<p></p>',
        'cta_button_label' => '',
        'cta_button_url' => '/contact',
        'city' => '',
        'local_page_type' => '',
        'local_advantages_json' => json_encode([], JSON_UNESCAPED_UNICODE),
        'nearby_cities_json' => json_encode([], JSON_UNESCAPED_UNICODE),
        'status' => 'draft',
        'updated_at' => null,
    ], $seed);
}

function cms_page_sections(array $page): array
{
    $sections = json_decode((string) ($page['sections_json'] ?? '[]'), true);
    return is_array($sections) && $sections !== [] ? $sections : cms_blank_sections();
}

function cms_json_list(?string $json): array
{
    $decoded = json_decode((string) $json, true);
    return is_array($decoded) ? array_values(array_filter(array_map('trim', $decoded), static fn ($item) => $item !== '')) : [];
}

function cms_main_pages(): array
{
    $defaults = cms_main_page_defaults();
    $statement = cms_db()->query("SELECT id, page_key, slug, title, status, updated_at FROM cms_pages WHERE page_type = 'main'");
    $rows = $statement->fetchAll();
    $byKey = [];

    foreach ($rows as $row) {
        $byKey[$row['page_key']] = $row;
    }

    $result = [];
    foreach ($defaults as $key => $default) {
        $row = $byKey[$key] ?? [];
        $result[] = array_merge($default, $row);
    }

    return $result;
}

function cms_main_page(string $pageKey): array
{
    $statement = cms_db()->prepare('SELECT * FROM cms_pages WHERE page_type = ? AND page_key = ? LIMIT 1');
    $statement->execute(['main', $pageKey]);
    $row = $statement->fetch();

    if ($row) {
        return $row;
    }

    $defaults = cms_main_page_defaults();
    if (!isset($defaults[$pageKey])) {
        throw new RuntimeException('Page principale introuvable.');
    }

    return cms_blank_page($defaults[$pageKey]);
}

function cms_local_pages(): array
{
    $statement = cms_db()->query("SELECT id, title, city, slug, status, updated_at FROM cms_pages WHERE page_type = 'local' ORDER BY city ASC, title ASC");
    return $statement->fetchAll();
}

function cms_local_page(?int $id): array
{
    if ($id === null) {
        return cms_blank_page(['page_type' => 'local', 'slug' => '', 'title' => 'Nouvelle page locale']);
    }

    $statement = cms_db()->prepare('SELECT * FROM cms_pages WHERE id = ? AND page_type = ? LIMIT 1');
    $statement->execute([$id, 'local']);
    $row = $statement->fetch();

    if (!$row) {
        throw new RuntimeException('Page locale introuvable.');
    }

    return $row;
}

function cms_slugify(string $value): string
{
    $value = trim($value);
    if ($value === '' || $value === '/') {
        return '/';
    }

    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: $value;
    $value = trim($value, '-');

    return '/' . $value;
}

function cms_parse_lines(string $value): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value) ?: []), static fn ($item) => $item !== ''));
}

function cms_parse_stats(string $value): array
{
    $stats = [];

    foreach (cms_parse_lines($value) as $line) {
        $parts = explode('|', $line, 2);
        $label = trim($parts[0] ?? '');
        $statValue = trim($parts[1] ?? '');

        if ($label !== '' && $statValue !== '') {
            $stats[] = ['label' => $label, 'value' => $statValue];
        }
    }

    return $stats;
}

function cms_page_payload_from_request(string $pageType, ?string $pageKey = null): array
{
    $slug = trim((string) ($_POST['slug'] ?? ''));
    if ($pageType === 'local') {
        $slug = cms_slugify($slug !== '' ? $slug : (string) ($_POST['city'] ?? $_POST['title'] ?? ''));
    } elseif ($slug === '') {
        $slug = '/';
    } elseif ($slug !== '/') {
        $slug = '/' . trim($slug, '/');
    }

    $sections = [];
    $sectionCount = max(1, (int) ($_POST['section_count'] ?? 0));

    for ($index = 0; $index < $sectionCount; $index++) {
        $sections[] = [
            'eyebrow' => trim((string) ($_POST['section_eyebrow'][$index] ?? '')),
            'title' => trim((string) ($_POST['section_title'][$index] ?? '')),
            'text' => trim((string) ($_POST['section_text'][$index] ?? '')),
            'image' => trim((string) ($_POST['section_image'][$index] ?? '')),
            'imageAlt' => trim((string) ($_POST['section_image_alt'][$index] ?? '')),
            'buttonLabel' => trim((string) ($_POST['section_button_label'][$index] ?? '')),
            'buttonUrl' => trim((string) ($_POST['section_button_url'][$index] ?? '')),
            'items' => cms_parse_lines((string) ($_POST['section_items'][$index] ?? '')),
            'stats' => cms_parse_stats((string) ($_POST['section_stats'][$index] ?? '')),
        ];
    }

    return [
        'page_type' => $pageType,
        'page_key' => $pageKey,
        'slug' => $slug,
        'title' => trim((string) ($_POST['title'] ?? '')),
        'meta_description' => trim((string) ($_POST['meta_description'] ?? '')),
        'is_indexable' => isset($_POST['is_indexable']) ? 1 : 0,
        'h1' => trim((string) ($_POST['h1'] ?? '')),
        'hero_title' => trim((string) ($_POST['hero_title'] ?? '')),
        'hero_subtitle' => trim((string) ($_POST['hero_subtitle'] ?? '')),
        'hero_image' => trim((string) ($_POST['hero_image'] ?? '')),
        'hero_image_alt' => trim((string) ($_POST['hero_image_alt'] ?? '')),
        'intro_html' => trim((string) ($_POST['intro_html'] ?? '')),
        'sections_json' => json_encode($sections, JSON_UNESCAPED_UNICODE),
        'cta_title' => trim((string) ($_POST['cta_title'] ?? '')),
        'cta_text' => trim((string) ($_POST['cta_text'] ?? '')),
        'cta_button_label' => trim((string) ($_POST['cta_button_label'] ?? '')),
        'cta_button_url' => trim((string) ($_POST['cta_button_url'] ?? '/contact')),
        'city' => trim((string) ($_POST['city'] ?? '')),
        'local_page_type' => trim((string) ($_POST['local_page_type'] ?? '')),
        'local_advantages_json' => json_encode(cms_parse_lines((string) ($_POST['local_advantages'] ?? '')), JSON_UNESCAPED_UNICODE),
        'nearby_cities_json' => json_encode(cms_parse_lines((string) ($_POST['nearby_cities'] ?? '')), JSON_UNESCAPED_UNICODE),
        'status' => (($_POST['status'] ?? 'draft') === 'published') ? 'published' : 'draft',
    ];
}

function cms_validate_page_payload(array $payload): array
{
    $errors = [];

    foreach (['title', 'meta_description', 'h1', 'hero_title', 'intro_html', 'cta_title', 'cta_button_label', 'cta_button_url'] as $field) {
        if (trim((string) ($payload[$field] ?? '')) === '') {
            $errors[] = 'Tous les champs essentiels doivent être remplis.';
            break;
        }
    }

    if ($payload['page_type'] === 'local') {
        foreach (['city', 'local_page_type'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                $errors[] = 'Une page locale doit avoir une ville et un type de page.';
                break;
            }
        }
    }

    return $errors;
}

function cms_save_page(array $payload, ?int $id = null): int
{
    $pdo = cms_db();
    $publishedAt = $payload['status'] === 'published' ? date('Y-m-d H:i:s') : null;

    if ($id === null) {
        $statement = $pdo->prepare(
            'INSERT INTO cms_pages (
                page_type, page_key, slug, title, meta_description, is_indexable, h1, hero_title,
                hero_subtitle, hero_image, hero_image_alt, intro_html, sections_json, cta_title,
                cta_text, cta_button_label, cta_button_url, city, local_page_type,
                local_advantages_json, nearby_cities_json, status, published_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' 
        );
        $statement->execute([
            $payload['page_type'],
            $payload['page_key'],
            $payload['slug'],
            $payload['title'],
            $payload['meta_description'],
            $payload['is_indexable'],
            $payload['h1'],
            $payload['hero_title'],
            $payload['hero_subtitle'],
            $payload['hero_image'] ?: null,
            $payload['hero_image_alt'] ?: null,
            $payload['intro_html'],
            $payload['sections_json'],
            $payload['cta_title'],
            $payload['cta_text'],
            $payload['cta_button_label'],
            $payload['cta_button_url'],
            $payload['city'] ?: null,
            $payload['local_page_type'] ?: null,
            $payload['local_advantages_json'],
            $payload['nearby_cities_json'],
            $payload['status'],
            $publishedAt,
        ]);

        return (int) $pdo->lastInsertId();
    }

    $statement = $pdo->prepare(
        'UPDATE cms_pages SET
            page_type = ?, page_key = ?, slug = ?, title = ?, meta_description = ?, is_indexable = ?, h1 = ?,
            hero_title = ?, hero_subtitle = ?, hero_image = ?, hero_image_alt = ?, intro_html = ?, sections_json = ?,
            cta_title = ?, cta_text = ?, cta_button_label = ?, cta_button_url = ?, city = ?, local_page_type = ?,
            local_advantages_json = ?, nearby_cities_json = ?, status = ?,
            published_at = CASE WHEN ? = "published" THEN COALESCE(published_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $statement->execute([
        $payload['page_type'],
        $payload['page_key'],
        $payload['slug'],
        $payload['title'],
        $payload['meta_description'],
        $payload['is_indexable'],
        $payload['h1'],
        $payload['hero_title'],
        $payload['hero_subtitle'],
        $payload['hero_image'] ?: null,
        $payload['hero_image_alt'] ?: null,
        $payload['intro_html'],
        $payload['sections_json'],
        $payload['cta_title'],
        $payload['cta_text'],
        $payload['cta_button_label'],
        $payload['cta_button_url'],
        $payload['city'] ?: null,
        $payload['local_page_type'] ?: null,
        $payload['local_advantages_json'],
        $payload['nearby_cities_json'],
        $payload['status'],
        $payload['status'],
        $id,
    ]);

    return $id;
}

function cms_delete_local_page(int $id): void
{
    $statement = cms_db()->prepare('DELETE FROM cms_pages WHERE id = ? AND page_type = ? LIMIT 1');
    $statement->execute([$id, 'local']);
}

function cms_media_items(): array
{
    $statement = cms_db()->query('SELECT * FROM cms_media ORDER BY created_at DESC, id DESC');
    return $statement->fetchAll();
}

function cms_upload_directory(): string
{
    $config = cms_config();
    $directory = $config['root'] . '/' . $config['upload_dir'];

    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    return $directory;
}

function cms_store_uploaded_media(array $file, string $title = '', string $altText = ''): void
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le téléversement a échoué.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: 'application/octet-stream';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mimeType])) {
        throw new RuntimeException('Formats autorisés : JPG, PNG, WebP.');
    }

    $extension = $allowed[$mimeType];
    $baseName = pathinfo((string) ($file['name'] ?? 'image'), PATHINFO_FILENAME);
    $safeBaseName = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($baseName)), '-');
    $fileName = ($safeBaseName !== '' ? $safeBaseName : 'media') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = cms_upload_directory() . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Impossible d’enregistrer l’image sur le serveur.');
    }

    $config = cms_config();
    $publicUrl = $config['upload_public_base'] . '/' . $fileName;
    $statement = cms_db()->prepare(
        'INSERT INTO cms_media (original_name, file_name, public_url, mime_type, size_bytes, alt_text, title)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        (string) ($file['name'] ?? $fileName),
        $fileName,
        $publicUrl,
        $mimeType,
        (int) ($file['size'] ?? 0),
        $altText !== '' ? $altText : null,
        $title !== '' ? $title : null,
    ]);
}

function cms_admin_users(): array
{
    $statement = cms_db()->query('SELECT id, name, email, role, is_active, created_at, updated_at FROM cms_admin_users ORDER BY id ASC');
    return $statement->fetchAll();
}

function cms_admin_user(?int $id): ?array
{
    if ($id === null) {
        return null;
    }

    $statement = cms_db()->prepare('SELECT id, name, email, role, is_active FROM cms_admin_users WHERE id = ? LIMIT 1');
    $statement->execute([$id]);
    $row = $statement->fetch();

    return $row ?: null;
}

function cms_save_admin_user(array $payload, ?int $id = null): int
{
    $pdo = cms_db();
    $existingUsers = cms_admin_users();

    if ($id === null && count($existingUsers) >= 2) {
        throw new RuntimeException('Ce CMS est limité à deux comptes admin.');
    }

    $password = (string) ($payload['password'] ?? '');
    if ($id === null && trim($password) === '') {
        throw new RuntimeException('Le mot de passe est obligatoire pour créer un compte.');
    }

    if ($id === null) {
        $statement = $pdo->prepare('INSERT INTO cms_admin_users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)');
        $statement->execute([
            trim((string) $payload['name']),
            strtolower(trim((string) $payload['email'])),
            password_hash($password, PASSWORD_DEFAULT),
            'admin',
            !empty($payload['is_active']) ? 1 : 0,
        ]);

        return (int) $pdo->lastInsertId();
    }

    if (trim($password) === '') {
        $statement = $pdo->prepare('UPDATE cms_admin_users SET name = ?, email = ?, is_active = ? WHERE id = ?');
        $statement->execute([
            trim((string) $payload['name']),
            strtolower(trim((string) $payload['email'])),
            !empty($payload['is_active']) ? 1 : 0,
            $id,
        ]);
    } else {
        $statement = $pdo->prepare('UPDATE cms_admin_users SET name = ?, email = ?, password_hash = ?, is_active = ? WHERE id = ?');
        $statement->execute([
            trim((string) $payload['name']),
            strtolower(trim((string) $payload['email'])),
            password_hash($password, PASSWORD_DEFAULT),
            !empty($payload['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    return $id;
}

function cms_attempt_login(string $email, string $password): ?array
{
    $statement = cms_db()->prepare('SELECT id, name, email, password_hash, role, is_active FROM cms_admin_users WHERE email = ? LIMIT 1');
    $statement->execute([strtolower(trim($email))]);
    $user = $statement->fetch();

    if (!$user || (int) $user['is_active'] !== 1) {
        return null;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function cms_login(array $user): void
{
    cms_bootstrap_session();
    session_regenerate_id(true);
    $_SESSION['admin_user'] = $user;
}

function cms_current_admin(): ?array
{
    cms_bootstrap_session();
    $user = $_SESSION['admin_user'] ?? null;
    return is_array($user) ? $user : null;
}

function cms_require_admin(): array
{
    $user = cms_current_admin();
    if (!$user) {
        cms_redirect('/admin/login');
    }

    return $user;
}

function cms_logout(): void
{
    cms_bootstrap_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function cms_public_page_by_path(string $path): ?array
{
    $normalized = $path === '/' ? '/' : '/' . trim($path, '/');
    $statement = cms_db()->prepare("SELECT * FROM cms_pages WHERE slug = ? AND status = 'published' LIMIT 1");
    $statement->execute([$normalized]);
    $row = $statement->fetch();

    return $row ?: null;
}

function cms_public_navigation(): array
{
    $items = [];
    foreach (cms_main_pages() as $page) {
        $items[] = [
            'label' => $page['title'],
            'href' => $page['slug'],
        ];
    }

    return $items;
}

require_once __DIR__ . '/views.php';