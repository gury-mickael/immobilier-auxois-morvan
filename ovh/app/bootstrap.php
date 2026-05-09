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

function cms_apply_global_output_replacements(): void
{
    static $started = false;

    if ($started) {
        return;
    }

    $started = true;
    ob_start(static fn (string $buffer): string => str_replace('Roulier', 'Roullier', $buffer));
}

function cms_normalize_upload_public_base(?string $value): string
{
    $base = trim((string) ($value ?? '/uploads/cms'));

    if ($base === '') {
        $base = '/uploads/cms';
    }

    if (preg_match('#^https?://#i', $base) === 1) {
        $path = parse_url($base, PHP_URL_PATH);
        $base = is_string($path) && $path !== '' ? $path : '/uploads/cms';
    }

    $base = '/' . trim($base, '/');
    $uploadsPosition = strpos($base, '/uploads/');

    if ($uploadsPosition !== false && $uploadsPosition > 0) {
        $base = substr($base, $uploadsPosition);
    }

    return rtrim($base, '/') ?: '/uploads/cms';
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
        'upload_public_base' => cms_normalize_upload_public_base($get('UPLOAD_PUBLIC_BASE', '/uploads/cms')),
        'install_token' => (string) $get('INSTALL_TOKEN', ''),
    ];

    return $config;
}

cms_apply_global_output_replacements();

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
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    return $pdo;
}

function cms_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function strftime_safe(?int $timestamp = null): string
{
    $months = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];
    $ts = $timestamp ?? time();
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function cms_relative_time(int $timestamp): string
{
    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'à l\'instant';
    }
    if ($diff < 3600) {
        $m = (int) ($diff / 60);
        return 'il y a ' . $m . ' min';
    }
    if ($diff < 86400) {
        $h = (int) ($diff / 3600);
        return 'il y a ' . $h . ' h';
    }
    if ($diff < 604800) {
        $d = (int) ($diff / 86400);
        return 'il y a ' . $d . ' j';
    }
    if ($diff < 2592000) {
        $w = (int) ($diff / 604800);
        return 'il y a ' . $w . ' sem.';
    }
    return date('d/m/Y', $timestamp);
}

function cms_base_url(): string
{
    $appUrl = cms_config()['app_url'] ?? '';
    $path = parse_url($appUrl, PHP_URL_PATH);

    if (!is_string($path)) {
        return '';
    }

    $path = trim($path, '/');

    return $path === '' ? '' : '/' . $path;
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
    $sessionToken = $_SESSION['csrf_token'] ?? null;

    if (!is_string($token) || $token === '' || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(419);
        exit('Jeton CSRF invalide.');
    }
}

function cms_default_settings(): array
{
    return [
        'site_name' => 'Immobilier Auxois Morvan',
        'baseline' => 'Mickael Gury et Marion Roullier accompagnent les projets immobiliers en Auxois et dans le Morvan.',
        'mickael_name' => 'Mickael Gury',
        'marion_name' => 'Marion Roullier',
        'mickael_photo' => '',
        'marion_photo' => '',
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

function cms_ensure_site_settings_media_columns(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo = cms_db();
    $statement = $pdo->prepare(
        "SELECT COLUMN_NAME
           FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cms_site_settings'
            AND COLUMN_NAME IN ('mickael_photo', 'marion_photo')"
    );
    $statement->execute();
    $existing = array_flip(array_column($statement->fetchAll(), 'COLUMN_NAME'));

    if (!isset($existing['mickael_photo'])) {
        $pdo->exec("ALTER TABLE cms_site_settings ADD COLUMN mickael_photo VARCHAR(255) DEFAULT NULL AFTER marion_name");
    }

    if (!isset($existing['marion_photo'])) {
        $pdo->exec("ALTER TABLE cms_site_settings ADD COLUMN marion_photo VARCHAR(255) DEFAULT NULL AFTER mickael_photo");
    }

    $done = true;
}

function cms_settings(): array
{
    cms_ensure_site_settings_media_columns();

    $defaults = cms_default_settings();
    $statement = cms_db()->query('SELECT * FROM cms_site_settings WHERE id = 1 LIMIT 1');
    $row = $statement->fetch();

    if (!$row) {
        return $defaults;
    }

    return array_merge($defaults, $row);
}

function cms_save_settings(array $payload): void
{
    cms_ensure_site_settings_media_columns();

    $statement = cms_db()->prepare(
        'INSERT INTO cms_site_settings (
            id, site_name, baseline, mickael_name, marion_name, phone, email, main_city,
            mickael_photo, marion_photo, covered_areas_json, facebook_url, instagram_url, iad_url, footer_text,
            main_cta_label, main_cta_url
         ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            site_name = VALUES(site_name),
            baseline = VALUES(baseline),
            mickael_name = VALUES(mickael_name),
            marion_name = VALUES(marion_name),
            mickael_photo = VALUES(mickael_photo),
            marion_photo = VALUES(marion_photo),
            phone = VALUES(phone),
            email = VALUES(email),
            main_city = VALUES(main_city),
            covered_areas_json = VALUES(covered_areas_json),
            facebook_url = VALUES(facebook_url),
            instagram_url = VALUES(instagram_url),
            iad_url = VALUES(iad_url),
            footer_text = VALUES(footer_text),
            main_cta_label = VALUES(main_cta_label),
            main_cta_url = VALUES(main_cta_url)'
    );

    $statement->execute([
        trim((string) ($payload['site_name'] ?? '')),
        trim((string) ($payload['baseline'] ?? '')),
        trim((string) ($payload['mickael_name'] ?? '')),
        trim((string) ($payload['marion_name'] ?? '')),
        trim((string) ($payload['phone'] ?? '')),
        trim((string) ($payload['email'] ?? '')),
        trim((string) ($payload['main_city'] ?? '')),
        trim((string) ($payload['mickael_photo'] ?? '')) ?: null,
        trim((string) ($payload['marion_photo'] ?? '')) ?: null,
        json_encode(cms_parse_lines((string) ($payload['covered_areas'] ?? '')), JSON_UNESCAPED_UNICODE),
        trim((string) ($payload['facebook_url'] ?? '')) ?: null,
        trim((string) ($payload['instagram_url'] ?? '')) ?: null,
        trim((string) ($payload['iad_url'] ?? '')) ?: null,
        trim((string) ($payload['footer_text'] ?? '')),
        trim((string) ($payload['main_cta_label'] ?? '')),
        trim((string) ($payload['main_cta_url'] ?? '')),
    ]);
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

function cms_blog_slugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: $value;
    return trim($value, '-');
}

function cms_blank_blog_post(array $seed = []): array
{
    return array_merge([
        'id' => null,
        'title' => '',
        'slug' => '',
        'excerpt' => '',
        'category' => 'Actualite',
        'featured_image' => '',
        'featured_image_alt' => '',
        'content_html' => '<p></p>',
        'meta_title' => '',
        'meta_description' => '',
        'is_indexable' => 1,
        'status' => 'draft',
        'published_at' => null,
        'created_at' => null,
        'updated_at' => null,
        'source' => 'database',
    ], $seed);
}

function cms_snapshot_blog_posts(): array
{
    $posts = cms_snapshot()['blogPosts'] ?? [];
    $result = [];

    foreach ($posts as $post) {
        if (!is_array($post)) {
            continue;
        }

        $slug = trim((string) ($post['slug'] ?? ''));
        $href = trim((string) ($post['href'] ?? ''));

        if ($slug === '' && preg_match('#^/blog/(.+)$#', $href, $matches) === 1) {
            $slug = trim((string) ($matches[1] ?? ''), '/');
        }

        if ($slug === '') {
            continue;
        }

        $result[] = cms_blank_blog_post([
            'title' => trim((string) ($post['title'] ?? '')),
            'slug' => $slug,
            'excerpt' => trim((string) ($post['excerpt'] ?? '')),
            'category' => trim((string) ($post['category'] ?? 'Actualite')),
            'featured_image' => trim((string) ($post['image'] ?? '')),
            'featured_image_alt' => trim((string) ($post['imageAlt'] ?? '')),
            'content_html' => trim((string) ($post['bodyHtml'] ?? '<p></p>')),
            'meta_title' => trim((string) ($post['metaTitle'] ?? $post['title'] ?? '')),
            'meta_description' => trim((string) ($post['metaDescription'] ?? $post['excerpt'] ?? '')),
            'is_indexable' => !empty($post['isIndexable']) ? 1 : 0,
            'status' => 'file',
            'published_at' => trim((string) ($post['date'] ?? '')) ?: null,
            'created_at' => trim((string) ($post['date'] ?? '')) ?: null,
            'source' => 'file',
        ]);
    }

    return $result;
}

function cms_blog_posts(): array
{
    $bySlug = [];

    foreach (cms_snapshot_blog_posts() as $post) {
        $bySlug[(string) $post['slug']] = $post;
    }

    $statement = cms_db()->query(
        'SELECT id, title, slug, excerpt, category, featured_image, featured_image_alt, content_html,
                meta_title, meta_description, is_indexable, status, published_at, created_at, updated_at
           FROM cms_blog_posts
          ORDER BY COALESCE(published_at, updated_at, created_at) DESC, title ASC'
    );

    foreach ($statement->fetchAll() as $row) {
        $bySlug[(string) $row['slug']] = cms_blank_blog_post(array_merge($row, ['source' => 'database']));
    }

    $posts = array_values($bySlug);
    usort($posts, static function (array $left, array $right): int {
        $leftDate = strtotime((string) ($left['published_at'] ?? $left['updated_at'] ?? $left['created_at'] ?? '')) ?: 0;
        $rightDate = strtotime((string) ($right['published_at'] ?? $right['updated_at'] ?? $right['created_at'] ?? '')) ?: 0;

        if ($leftDate === $rightDate) {
            return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        }

        return $rightDate <=> $leftDate;
    });

    return $posts;
}

function cms_blog_post(?string $slug): ?array
{
    if ($slug === null || trim($slug) === '') {
        return cms_blank_blog_post();
    }

    $normalizedSlug = cms_blog_slugify($slug);

    $statement = cms_db()->prepare(
        'SELECT id, title, slug, excerpt, category, featured_image, featured_image_alt, content_html,
                meta_title, meta_description, is_indexable, status, published_at, created_at, updated_at
           FROM cms_blog_posts
          WHERE slug = ?
          LIMIT 1'
    );
    $statement->execute([$normalizedSlug]);
    $row = $statement->fetch();

    if ($row) {
        return cms_blank_blog_post(array_merge($row, ['source' => 'database']));
    }

    foreach (cms_snapshot_blog_posts() as $post) {
        if ((string) $post['slug'] === $normalizedSlug) {
            return $post;
        }
    }

    return null;
}

function cms_blog_payload_from_request(?string $fallbackSlug = null): array
{
    $submittedSlug = trim((string) ($_POST['slug'] ?? ''));
    $slugSource = $submittedSlug !== '' ? $submittedSlug : ($fallbackSlug ?: (string) ($_POST['title'] ?? ''));

    return [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => cms_blog_slugify($slugSource),
        'excerpt' => trim((string) ($_POST['excerpt'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'featured_image' => trim((string) ($_POST['featured_image'] ?? '')),
        'featured_image_alt' => trim((string) ($_POST['featured_image_alt'] ?? '')),
        'content_html' => trim((string) ($_POST['content_html'] ?? '')),
        'meta_title' => trim((string) ($_POST['meta_title'] ?? '')),
        'meta_description' => trim((string) ($_POST['meta_description'] ?? '')),
        'is_indexable' => isset($_POST['is_indexable']) ? 1 : 0,
        'status' => (($_POST['status'] ?? 'draft') === 'published') ? 'published' : 'draft',
    ];
}

function cms_validate_blog_payload(array $payload): array
{
    $errors = [];

    foreach (['title', 'slug', 'excerpt', 'category', 'content_html', 'meta_title', 'meta_description'] as $field) {
        if (trim((string) ($payload[$field] ?? '')) === '') {
            $errors[] = 'Tous les champs essentiels de l\'article doivent être remplis.';
            break;
        }
    }

    return $errors;
}

function cms_save_blog_post(array $payload, ?int $id = null): int
{
    $pdo = cms_db();

    if ($id === null) {
        $statement = $pdo->prepare(
            'INSERT INTO cms_blog_posts (
                title, slug, excerpt, category, featured_image, featured_image_alt, content_html,
                meta_title, meta_description, is_indexable, status, published_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = "published" THEN NOW() ELSE NULL END)'
        );
        $statement->execute([
            $payload['title'],
            $payload['slug'],
            $payload['excerpt'],
            $payload['category'],
            $payload['featured_image'] ?: null,
            $payload['featured_image_alt'] ?: null,
            $payload['content_html'],
            $payload['meta_title'],
            $payload['meta_description'],
            $payload['is_indexable'],
            $payload['status'],
            $payload['status'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    $statement = $pdo->prepare(
        'UPDATE cms_blog_posts SET
            title = ?, slug = ?, excerpt = ?, category = ?, featured_image = ?, featured_image_alt = ?,
            content_html = ?, meta_title = ?, meta_description = ?, is_indexable = ?, status = ?,
            published_at = CASE WHEN ? = "published" THEN COALESCE(published_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $statement->execute([
        $payload['title'],
        $payload['slug'],
        $payload['excerpt'],
        $payload['category'],
        $payload['featured_image'] ?: null,
        $payload['featured_image_alt'] ?: null,
        $payload['content_html'],
        $payload['meta_title'],
        $payload['meta_description'],
        $payload['is_indexable'],
        $payload['status'],
        $payload['status'],
        $id,
    ]);

    return $id;
}

function cms_public_blog_posts(): array
{
    return array_values(array_filter(cms_blog_posts(), static function (array $post): bool {
        return in_array((string) ($post['status'] ?? 'draft'), ['published', 'file'], true);
    }));
}

function cms_public_blog_post(string $slug): ?array
{
    $post = cms_blog_post($slug);

    if (!$post) {
        return null;
    }

    return in_array((string) ($post['status'] ?? 'draft'), ['published', 'file'], true) ? $post : null;
}

function cms_media_items(): array
{
    $statement = cms_db()->query('SELECT * FROM cms_media ORDER BY created_at DESC, id DESC');
    return $statement->fetchAll();
}

function cms_media_public_url(array $item): string
{
    $publicUrl = trim((string) ($item['public_url'] ?? ''));
    $fileName = trim((string) ($item['file_name'] ?? ''));

    if ($publicUrl !== '' && preg_match('#^https?://#i', $publicUrl) === 1) {
        return $publicUrl;
    }

    $config = cms_config();
    $uploadBase = rtrim((string) ($config['upload_public_base'] ?? '/uploads/cms'), '/');
    $candidates = [];

    if ($publicUrl !== '') {
        $normalizedPublicUrl = '/' . ltrim($publicUrl, '/');
        $candidates[] = $normalizedPublicUrl;

        $uploadsPosition = strpos($normalizedPublicUrl, '/uploads/');
        if ($uploadsPosition !== false && $uploadsPosition > 0) {
            $candidates[] = substr($normalizedPublicUrl, $uploadsPosition);
        }
    }

    if ($fileName !== '') {
        $candidates[] = $uploadBase . '/' . ltrim($fileName, '/');
        $candidates[] = '/uploads/' . ltrim($fileName, '/');
    }

    if ($publicUrl !== '' && !str_contains($publicUrl, '/')) {
        $candidates[] = $uploadBase . '/' . $publicUrl;
        $candidates[] = '/uploads/' . $publicUrl;
    }

    foreach ([$publicUrl, $fileName] as $sourceName) {
        $sourceName = trim((string) $sourceName);
        if ($sourceName === '') {
            continue;
        }

        $baseName = basename($sourceName);
        $legacyNames = [$baseName];

        $withoutDatePrefix = preg_replace('/^\d{4}-\d{2}-/', '', $baseName);
        if (is_string($withoutDatePrefix) && $withoutDatePrefix !== $baseName) {
            $legacyNames[] = $withoutDatePrefix;
        }

        foreach ($legacyNames as $legacyName) {
            $legacyNames[] = (string) preg_replace('/-\d+x\d+(?=\.[^.]+$)/', '', $legacyName);
        }

        foreach (array_unique(array_filter($legacyNames)) as $legacyName) {
            $candidates[] = $uploadBase . '/' . $legacyName;
            $candidates[] = '/uploads/' . $legacyName;
        }
    }

    $seen = [];
    foreach ($candidates as $candidate) {
        $normalized = '/' . ltrim($candidate, '/');
        if (isset($seen[$normalized])) {
            continue;
        }

        $seen[$normalized] = true;
        $path = cms_config()['root'] . $normalized;
        if (is_file($path)) {
            return $normalized;
        }
    }

    return $publicUrl !== '' ? '/' . ltrim($publicUrl, '/') : $uploadBase . '/' . ltrim($fileName, '/');
}

function cms_media_is_available(array $item): bool
{
    $publicUrl = cms_media_public_url($item);

    if (preg_match('#^https?://#i', $publicUrl) === 1) {
        return true;
    }

    return is_file(cms_config()['root'] . '/' . ltrim($publicUrl, '/'));
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

function cms_can_optimize_uploaded_image(string $mimeType): bool
{
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) {
        return false;
    }

    return match ($mimeType) {
        'image/jpeg' => function_exists('imagecreatefromjpeg') && function_exists('imagejpeg'),
        'image/png' => function_exists('imagecreatefrompng') && function_exists('imagepng'),
        'image/webp' => function_exists('imagecreatefromwebp') && function_exists('imagewebp'),
        default => false,
    };
}

function cms_write_optimized_upload(string $tmpName, string $destination, string $mimeType): bool
{
    if (!cms_can_optimize_uploaded_image($mimeType)) {
        return false;
    }

    $imageInfo = @getimagesize($tmpName);
    if (!is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1])) {
        return false;
    }

    [$sourceWidth, $sourceHeight] = $imageInfo;
    $maxWidth = 2200;
    $maxHeight = 2200;
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
    $targetWidth = max(1, (int) round($sourceWidth * $ratio));
    $targetHeight = max(1, (int) round($sourceHeight * $ratio));

    $source = match ($mimeType) {
        'image/jpeg' => @imagecreatefromjpeg($tmpName),
        'image/png' => @imagecreatefrompng($tmpName),
        'image/webp' => @imagecreatefromwebp($tmpName),
        default => false,
    };

    if (!$source) {
        return false;
    }

    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    if (!$target) {
        imagedestroy($source);
        return false;
    }

    if (in_array($mimeType, ['image/png', 'image/webp'], true)) {
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
    } else {
        $background = imagecolorallocate($target, 255, 255, 255);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $background);
    }

    $resampled = imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
    if (!$resampled) {
        imagedestroy($source);
        imagedestroy($target);
        return false;
    }

    $written = match ($mimeType) {
        'image/jpeg' => imagejpeg($target, $destination, 82),
        'image/png' => imagepng($target, $destination, 6),
        'image/webp' => imagewebp($target, $destination, 82),
        default => false,
    };

    imagedestroy($source);
    imagedestroy($target);

    return $written;
}

function cms_store_uploaded_media(array $file, string $title = '', string $altText = ''): string
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

    $stored = cms_write_optimized_upload($tmpName, $destination, $mimeType);

    if (!$stored && !move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Impossible d’enregistrer l’image sur le serveur.');
    }

    $config = cms_config();
    $publicUrl = $config['upload_public_base'] . '/' . $fileName;
    $storedSize = is_file($destination) ? (int) filesize($destination) : (int) ($file['size'] ?? 0);
    $statement = cms_db()->prepare(
        'INSERT INTO cms_media (original_name, file_name, public_url, mime_type, size_bytes, alt_text, title)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        (string) ($file['name'] ?? $fileName),
        $fileName,
        $publicUrl,
        $mimeType,
        $storedSize,
        $altText !== '' ? $altText : null,
        $title !== '' ? $title : null,
    ]);

    return $publicUrl;
}

function cms_handle_contact_request(array $settings): array
{
    $trap = trim((string) ($_POST['website'] ?? ''));

    if ($trap !== '') {
        return [];
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $project = trim((string) ($_POST['project'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $privacy = (string) ($_POST['privacy'] ?? '');
    $errors = [];

    if ($name === '') {
        $errors[] = 'Indiquez votre nom.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Indiquez une adresse email valide.';
    }

    if ($subject === '') {
        $errors[] = 'Choisissez l’objet de votre demande.';
    }

    if ($message === '') {
        $errors[] = 'Ajoutez votre message.';
    }

    if ($privacy !== '1') {
        $errors[] = 'Validez l’autorisation de contact.';
    }

    if ($errors !== []) {
        return $errors;
    }

    $fullSubject = trim(implode(' — ', array_filter([$subject, $project, $location])));
    $statement = cms_db()->prepare(
        'INSERT INTO cms_contact_requests (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $name,
        $email,
        $phone !== '' ? $phone : null,
        $fullSubject !== '' ? $fullSubject : null,
        $message,
    ]);

    $to = trim((string) ($settings['email'] ?? ''));
    if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $body = "Nouvelle demande depuis le site\n\n"
            . "Nom : {$name}\n"
            . "Email : {$email}\n"
            . "Téléphone : {$phone}\n"
            . "Projet : {$project}\n"
            . "Localisation : {$location}\n"
            . "Objet : {$subject}\n\n"
            . $message;
        @mail($to, 'Nouvelle demande de contact', $body, 'Reply-To: ' . $email);
    }

    return [];
}

function cms_ensure_estimation_requests_table(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    cms_db()->exec(
        "CREATE TABLE IF NOT EXISTS cms_estimation_requests (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            property_type VARCHAR(100) NOT NULL,
            room_count VARCHAR(100) NOT NULL,
            property_condition VARCHAR(100) NOT NULL,
            living_surface VARCHAR(100) NOT NULL,
            land_surface VARCHAR(100) NOT NULL,
            commune VARCHAR(150) NOT NULL,
            postal_code VARCHAR(20) DEFAULT NULL,
            address_details TEXT NOT NULL,
            goal VARCHAR(150) NOT NULL,
            project_timeline VARCHAR(100) NOT NULL,
            first_name VARCHAR(150) NOT NULL,
            last_name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            contact_consent TINYINT(1) NOT NULL DEFAULT 0,
            outside_area TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'new',
            internal_notes LONGTEXT DEFAULT NULL,
            source VARCHAR(150) NOT NULL DEFAULT 'formulaire estimation en ligne',
            utm_source VARCHAR(190) DEFAULT NULL,
            utm_campaign VARCHAR(190) DEFAULT NULL,
            utm_content VARCHAR(190) DEFAULT NULL,
            utm_medium VARCHAR(190) DEFAULT NULL,
            origin_page VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_cms_estimation_requests_status (status),
            KEY idx_cms_estimation_requests_commune (commune),
            KEY idx_cms_estimation_requests_created_at (created_at),
            KEY idx_cms_estimation_requests_utm_campaign (utm_campaign)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $done = true;
}

function cms_estimation_statuses(): array
{
    return [
        'new' => 'Nouveau',
        'contacted' => 'Contacté',
        'appointment-booked' => 'Rendez-vous pris',
        'valuation-completed' => 'Estimation réalisée',
        'mandate-signed' => 'Mandat signé',
        'lost' => 'Perdu',
    ];
}

function cms_estimation_request_payload_from_request(): array
{
    return [
        'property_type' => trim((string) ($_POST['property_type'] ?? '')),
        'room_count' => trim((string) ($_POST['room_count'] ?? '')),
        'property_condition' => trim((string) ($_POST['property_condition'] ?? '')),
        'living_surface' => trim((string) ($_POST['living_surface'] ?? '')),
        'land_surface' => trim((string) ($_POST['land_surface'] ?? '')),
        'commune' => trim((string) ($_POST['commune'] ?? '')),
        'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
        'address_details' => trim((string) ($_POST['address_details'] ?? '')),
        'goal' => trim((string) ($_POST['goal'] ?? '')),
        'project_timeline' => trim((string) ($_POST['project_timeline'] ?? '')),
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'contact_consent' => (string) ($_POST['contact_consent'] ?? '') === '1' ? 1 : 0,
        'outside_area' => (string) ($_POST['outside_area'] ?? '') === '1' ? 1 : 0,
        'status' => 'new',
        'internal_notes' => '',
        'source' => trim((string) ($_POST['source'] ?? 'formulaire estimation en ligne')),
        'utm_source' => trim((string) ($_POST['utm_source'] ?? '')),
        'utm_campaign' => trim((string) ($_POST['utm_campaign'] ?? '')),
        'utm_content' => trim((string) ($_POST['utm_content'] ?? '')),
        'utm_medium' => trim((string) ($_POST['utm_medium'] ?? '')),
        'origin_page' => trim((string) ($_POST['origin_page'] ?? cms_url('/estimation-en-ligne'))),
    ];
}

function cms_validate_estimation_request_payload(array $payload): array
{
    $errors = [];

    foreach (['property_type', 'room_count', 'property_condition', 'living_surface', 'land_surface', 'commune', 'address_details', 'goal', 'project_timeline', 'first_name', 'last_name', 'email', 'phone'] as $field) {
        if (trim((string) ($payload[$field] ?? '')) === '') {
            $errors[] = 'Merci de compléter toutes les étapes avant d’envoyer votre demande.';
            break;
        }
    }

    if ($payload['email'] === '' || !filter_var((string) $payload['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Indiquez une adresse email valide.';
    }

    $phoneDigits = preg_replace('/\D+/', '', (string) ($payload['phone'] ?? ''));
    if ($phoneDigits === null || strlen($phoneDigits) < 9) {
        $errors[] = 'Indiquez un numéro de téléphone valide.';
    }

    if ((int) ($payload['contact_consent'] ?? 0) !== 1) {
        $errors[] = 'Vous devez accepter d’être recontacté au sujet de votre demande d’estimation.';
    }

    return array_values(array_unique($errors));
}

function cms_save_estimation_request(array $payload): int
{
    cms_ensure_estimation_requests_table();

    $statement = cms_db()->prepare(
        'INSERT INTO cms_estimation_requests (
            property_type, room_count, property_condition, living_surface, land_surface,
            commune, postal_code, address_details, goal, project_timeline,
            first_name, last_name, email, phone, contact_consent, outside_area,
            status, internal_notes, source, utm_source, utm_campaign, utm_content, utm_medium, origin_page
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $payload['property_type'],
        $payload['room_count'],
        $payload['property_condition'],
        $payload['living_surface'],
        $payload['land_surface'],
        $payload['commune'],
        $payload['postal_code'] !== '' ? $payload['postal_code'] : null,
        $payload['address_details'],
        $payload['goal'],
        $payload['project_timeline'],
        $payload['first_name'],
        $payload['last_name'],
        strtolower($payload['email']),
        $payload['phone'],
        $payload['contact_consent'],
        $payload['outside_area'],
        'new',
        null,
        $payload['source'] !== '' ? $payload['source'] : 'formulaire estimation en ligne',
        $payload['utm_source'] !== '' ? $payload['utm_source'] : null,
        $payload['utm_campaign'] !== '' ? $payload['utm_campaign'] : null,
        $payload['utm_content'] !== '' ? $payload['utm_content'] : null,
        $payload['utm_medium'] !== '' ? $payload['utm_medium'] : null,
        $payload['origin_page'] !== '' ? $payload['origin_page'] : null,
    ]);

    return (int) cms_db()->lastInsertId();
}

function cms_estimation_request(int $id): ?array
{
    cms_ensure_estimation_requests_table();

    $statement = cms_db()->prepare('SELECT * FROM cms_estimation_requests WHERE id = ? LIMIT 1');
    $statement->execute([$id]);
    $row = $statement->fetch();

    return $row ?: null;
}

function cms_estimation_requests(array $filters = []): array
{
    cms_ensure_estimation_requests_table();

    $where = [];
    $params = [];

    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $needle = '%' . $search . '%';
        $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR commune LIKE ?)';
        array_push($params, $needle, $needle, $needle, $needle, $needle);
    }

    $status = trim((string) ($filters['status'] ?? ''));
    if ($status !== '' && isset(cms_estimation_statuses()[$status])) {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    $commune = trim((string) ($filters['commune'] ?? ''));
    if ($commune !== '') {
        $where[] = 'commune LIKE ?';
        $params[] = '%' . $commune . '%';
    }

    $utmCampaign = trim((string) ($filters['utm_campaign'] ?? ''));
    if ($utmCampaign !== '') {
        $where[] = 'utm_campaign LIKE ?';
        $params[] = '%' . $utmCampaign . '%';
    }

    $sql = 'SELECT * FROM cms_estimation_requests';
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT 300';

    $statement = cms_db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function cms_recent_estimation_requests(int $limit = 5): array
{
    cms_ensure_estimation_requests_table();

    $statement = cms_db()->prepare('SELECT * FROM cms_estimation_requests WHERE status = ? ORDER BY created_at DESC, id DESC LIMIT ?');
    $statement->bindValue(1, 'new');
    $statement->bindValue(2, $limit, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll();
}

function cms_estimation_requests_count(?string $status = null): int
{
    cms_ensure_estimation_requests_table();

    if ($status !== null && isset(cms_estimation_statuses()[$status])) {
        $statement = cms_db()->prepare('SELECT COUNT(*) FROM cms_estimation_requests WHERE status = ?');
        $statement->execute([$status]);
        return (int) $statement->fetchColumn();
    }

    return (int) cms_db()->query('SELECT COUNT(*) FROM cms_estimation_requests')->fetchColumn();
}

function cms_update_estimation_request(int $id, array $payload): void
{
    cms_ensure_estimation_requests_table();

    $status = trim((string) ($payload['status'] ?? 'new'));
    if (!isset(cms_estimation_statuses()[$status])) {
        $status = 'new';
    }

    $statement = cms_db()->prepare('UPDATE cms_estimation_requests SET status = ?, internal_notes = ? WHERE id = ?');
    $statement->execute([
        $status,
        trim((string) ($payload['internal_notes'] ?? '')) ?: null,
        $id,
    ]);
}

function cms_delete_estimation_request(int $id): void
{
    cms_ensure_estimation_requests_table();
    $statement = cms_db()->prepare('DELETE FROM cms_estimation_requests WHERE id = ? LIMIT 1');
    $statement->execute([$id]);
}

function cms_mail_headers(?string $replyTo = null): string
{
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    if ($replyTo !== null && $replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    return implode("\r\n", $headers);
}

function cms_send_estimation_notifications(array $request, array $settings): void
{
    $adminEmail = 'mickael.gury@iadfrance.fr';
    $fullName = trim($request['first_name'] . ' ' . $request['last_name']);
    $commune = (string) ($request['commune'] ?? '');

    if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $adminBody = "Nouvelle demande d'estimation en ligne\n\n"
            . "Nom : {$fullName}\n"
            . "Email : {$request['email']}\n"
            . "Téléphone : {$request['phone']}\n"
            . "Commune : {$commune}\n"
            . "Type de bien : {$request['property_type']}\n"
            . "Objectif : {$request['goal']}\n"
            . "Délai : {$request['project_timeline']}\n"
            . "Campagne : " . (($request['utm_campaign'] ?? '') !== '' ? $request['utm_campaign'] : 'n/a') . "\n"
            . "Source : " . (($request['utm_source'] ?? '') !== '' ? $request['utm_source'] : 'n/a') . "\n";
        @mail($adminEmail, 'Nouvelle demande d’estimation en ligne', $adminBody, cms_mail_headers((string) $request['email']));
    }

    if (filter_var((string) ($request['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        $prospectBody = "Bonjour {$request['first_name']},\n\n"
            . "Merci pour votre demande d’estimation concernant votre bien situé à {$commune}.\n\n"
            . "J’ai bien reçu les informations transmises. Je vais les analyser et je vous recontacterai sous 24h pour vous donner un premier avis de valeur.\n\n"
            . "À très bientôt,\n\n"
            . trim((string) ($settings['mickael_name'] ?? 'Mickael Gury')) . "\n"
            . "Conseiller immobilier IAD";
        @mail((string) $request['email'], 'Votre demande d’estimation a bien été reçue', $prospectBody, cms_mail_headers($adminEmail));
    }
}

function cms_handle_estimation_request(array $settings): array
{
    $trap = trim((string) ($_POST['website'] ?? ''));
    if ($trap !== '') {
        return ['errors' => [], 'payload' => [], 'id' => null, 'ignored' => true];
    }

    $payload = cms_estimation_request_payload_from_request();
    $errors = cms_validate_estimation_request_payload($payload);

    if ($errors !== []) {
        return ['errors' => $errors, 'payload' => $payload, 'id' => null, 'ignored' => false];
    }

    $requestId = cms_save_estimation_request($payload);
    $request = cms_estimation_request($requestId);
    if ($request) {
        cms_send_estimation_notifications($request, $settings);
    }

    return ['errors' => [], 'payload' => $payload, 'id' => $requestId, 'ignored' => false];
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

function cms_install_token_is_valid(?string $providedToken): bool
{
    $configuredToken = cms_config()['install_token'] ?? '';

    return $configuredToken !== ''
        && is_string($providedToken)
        && $providedToken !== ''
        && hash_equals((string) $configuredToken, $providedToken);
}

function cms_run_sql_file(string $filePath): void
{
    if (!is_file($filePath)) {
        throw new RuntimeException('Fichier SQL introuvable : ' . $filePath);
    }

    $sql = trim((string) file_get_contents($filePath));

    if ($sql === '') {
        return;
    }

    cms_db()->exec($sql);
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

function cms_snapshot(): array
{
    static $snapshot = null;

    if (is_array($snapshot)) {
        return $snapshot;
    }

    $path = cms_config()['root'] . '/data/content-snapshot.json';

    if (!is_file($path)) {
        $snapshot = [
            'siteSettings' => [],
            'localPages' => [],
            'blogPosts' => [],
            'testimonials' => [],
            'services' => [],
            'areaDescriptions' => [],
            'areaImages' => [],
        ];

        return $snapshot;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    $snapshot = is_array($decoded) ? $decoded : [];

    return $snapshot;
}

function cms_format_long_date(string $value): string
{
    try {
        $date = new DateTimeImmutable($value);
    } catch (Throwable) {
        return $value;
    }

    $months = [
        1 => 'janvier',
        2 => 'fevrier',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'aout',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'decembre',
    ];

    $month = $months[(int) $date->format('n')] ?? $date->format('m');
    return $date->format('j') . ' ' . $month . ' ' . $date->format('Y');
}

require_once __DIR__ . '/views.php';