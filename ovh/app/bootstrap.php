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
    $getFirst = static function (array $keys, ?string $default = null) use ($get): ?string {
        foreach ($keys as $key) {
            $value = $get((string) $key, null);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
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
        'search_console_site_url' => (string) $getFirst(['GOOGLE_SEARCH_CONSOLE_SITE_URL', 'SEARCH_CONSOLE_SITE_URL'], 'sc-domain:immobilier-auxois-morvan.fr'),
        'search_console_credentials_path' => (string) $get('SEARCH_CONSOLE_CREDENTIALS_PATH', ''),
        'search_console_credentials_base64' => (string) $get('SEARCH_CONSOLE_CREDENTIALS_BASE64', ''),
        'google_client_id' => (string) $get('GOOGLE_CLIENT_ID', ''),
        'google_client_secret' => (string) $get('GOOGLE_CLIENT_SECRET', ''),
        'google_redirect_uri' => (string) $get('GOOGLE_REDIRECT_URI', ''),
        'google_analytics_id' => (string) $get('GOOGLE_ANALYTICS_ID', 'G-J6KKYF4C8P'),
        'openai_api_key' => (string) $get('OPENAI_API_KEY', ''),
        'openai_model' => (string) $get('OPENAI_MODEL', 'gpt-5.5'),
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

function cms_site_origin(): string
{
    $configured = rtrim((string) (cms_config()['app_url'] ?? ''), '/');
    if ($configured !== '') {
        return $configured;
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return '';
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $host;
}

function cms_absolute_url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return rtrim(cms_site_origin(), '/') . cms_url($path);
}

function cms_current_canonical_url(): string
{
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $base = cms_base_url();

    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }

    $path = '/' . trim($path, '/');
    return cms_absolute_url($path === '/' ? '/' : $path);
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
        'blog_enabled' => 0,
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
            AND COLUMN_NAME IN ('mickael_photo', 'marion_photo', 'blog_enabled')"
    );
    $statement->execute();
    $existing = array_flip(array_column($statement->fetchAll(), 'COLUMN_NAME'));

    if (!isset($existing['mickael_photo'])) {
        $pdo->exec("ALTER TABLE cms_site_settings ADD COLUMN mickael_photo VARCHAR(255) DEFAULT NULL AFTER marion_name");
    }

    if (!isset($existing['marion_photo'])) {
        $pdo->exec("ALTER TABLE cms_site_settings ADD COLUMN marion_photo VARCHAR(255) DEFAULT NULL AFTER mickael_photo");
    }

    if (!isset($existing['blog_enabled'])) {
        $pdo->exec("ALTER TABLE cms_site_settings ADD COLUMN blog_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER main_cta_url");
    }

    $done = true;
}

function cms_is_blog_public_enabled(array $settings): bool
{
    return (int) ($settings['blog_enabled'] ?? 0) === 1;
}

function cms_save_blog_visibility(bool $enabled): void
{
    cms_ensure_site_settings_media_columns();
    $statement = cms_db()->prepare('UPDATE cms_site_settings SET blog_enabled = ? WHERE id = 1');
    $statement->execute([$enabled ? 1 : 0]);
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
        'seo_intent' => 'estimation',
        'seo_status' => 'draft',
        'seo_focus_keyword' => '',
        'seo_secondary_keywords' => '',
        'seo_template' => '',
        'seo_notes' => '',
        'seo_faq_json' => json_encode([], JSON_UNESCAPED_UNICODE),
        'seo_internal_links_json' => json_encode([], JSON_UNESCAPED_UNICODE),
        'seo_is_primary' => 0,
        'status' => 'draft',
        'updated_at' => null,
    ], $seed);
}

function cms_ensure_page_seo_columns(): void
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
            AND TABLE_NAME = 'cms_pages'
            AND COLUMN_NAME IN ('seo_intent', 'seo_status', 'seo_focus_keyword', 'seo_secondary_keywords', 'seo_template', 'seo_notes', 'seo_faq_json', 'seo_internal_links_json', 'seo_is_primary')"
    );
    $statement->execute();
    $existing = array_flip(array_column($statement->fetchAll(), 'COLUMN_NAME'));

    $columns = [
        'seo_intent' => "ALTER TABLE cms_pages ADD COLUMN seo_intent VARCHAR(80) DEFAULT NULL AFTER nearby_cities_json",
        'seo_status' => "ALTER TABLE cms_pages ADD COLUMN seo_status VARCHAR(40) NOT NULL DEFAULT 'draft' AFTER seo_intent",
        'seo_focus_keyword' => "ALTER TABLE cms_pages ADD COLUMN seo_focus_keyword VARCHAR(190) DEFAULT NULL AFTER seo_status",
        'seo_secondary_keywords' => "ALTER TABLE cms_pages ADD COLUMN seo_secondary_keywords LONGTEXT DEFAULT NULL AFTER seo_focus_keyword",
        'seo_template' => "ALTER TABLE cms_pages ADD COLUMN seo_template VARCHAR(80) DEFAULT NULL AFTER seo_secondary_keywords",
        'seo_notes' => "ALTER TABLE cms_pages ADD COLUMN seo_notes LONGTEXT DEFAULT NULL AFTER seo_template",
        'seo_faq_json' => "ALTER TABLE cms_pages ADD COLUMN seo_faq_json LONGTEXT DEFAULT NULL AFTER seo_notes",
        'seo_internal_links_json' => "ALTER TABLE cms_pages ADD COLUMN seo_internal_links_json LONGTEXT DEFAULT NULL AFTER seo_faq_json",
        'seo_is_primary' => "ALTER TABLE cms_pages ADD COLUMN seo_is_primary TINYINT(1) NOT NULL DEFAULT 0 AFTER seo_internal_links_json",
    ];

    foreach ($columns as $column => $sql) {
        if (!isset($existing[$column])) {
            $pdo->exec($sql);
        }
    }

    $done = true;
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
    cms_ensure_page_seo_columns();
    $statement = cms_db()->query("SELECT * FROM cms_pages WHERE page_type = 'local' ORDER BY FIELD(seo_status, 'improve', 'review', 'draft', 'published'), city ASC, title ASC");
    $pages = $statement->fetchAll();

    foreach ($pages as &$page) {
        $page['seo_score'] = cms_seo_page_score($page)['score'];
    }

    return $pages;
}

function cms_local_page(?int $id): array
{
    cms_ensure_page_seo_columns();

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

function cms_parse_pairs(string $value, string $firstKey, string $secondKey): array
{
    $items = [];

    foreach (cms_parse_lines($value) as $line) {
        $parts = explode('|', $line, 2);
        $first = trim((string) ($parts[0] ?? ''));
        $second = trim((string) ($parts[1] ?? ''));
        if ($first !== '' && $second !== '') {
            $items[] = [$firstKey => $first, $secondKey => $second];
        }
    }

    return $items;
}

function cms_seo_page_intents(): array
{
    return [
        'estimation' => 'Estimation',
        'vente' => 'Vente',
        'viager' => 'Viager',
        'fonds-commerce' => 'Fonds de commerce',
        'marche-local' => 'Marché local',
        'guide-pratique' => 'Guide pratique',
        'page-pilier' => 'Page pilier',
        'autre' => 'Autre',
    ];
}

function cms_seo_page_statuses(): array
{
    return [
        'draft' => 'Brouillon',
        'review' => 'À relire',
        'published' => 'Publié',
        'improve' => 'À améliorer',
    ];
}

function cms_local_page_types(): array
{
    return [
        'estimation-immobiliere' => 'Estimation locale',
        'vendre-maison' => 'Vendre sa maison',
        'viager' => 'Viager',
        'fonds-de-commerce' => 'Fonds de commerce',
        'marche-local' => 'Marché local',
        'guide-pratique' => 'Guide pratique',
        'page-pilier' => 'Page pilier secteur',
    ];
}

function cms_seo_page_score(array $page): array
{
    $checks = [];
    $plainText = trim(strip_tags((string) ($page['intro_html'] ?? '') . ' ' . ($page['sections_json'] ?? '') . ' ' . ($page['cta_text'] ?? '')));
    $wordCount = str_word_count($plainText, 0, 'ÀÁÂÄÇÈÉÊËÌÍÎÏÒÓÔÖÙÚÛÜÝàáâäçèéêëìíîïòóôöùúûüýœŒ-');
    $focus = mb_strtolower(trim((string) ($page['seo_focus_keyword'] ?? '')));
    $title = (string) ($page['title'] ?? '');
    $h1 = (string) ($page['h1'] ?? '');
    $meta = (string) ($page['meta_description'] ?? '');
    $faqs = cms_json_objects((string) ($page['seo_faq_json'] ?? '[]'));
    $links = cms_json_objects((string) ($page['seo_internal_links_json'] ?? '[]'));

    $checks[] = ['label' => 'Mot clé principal renseigné', 'ok' => $focus !== ''];
    $checks[] = ['label' => 'Titre SEO entre 45 et 65 caractères', 'ok' => mb_strlen($title) >= 45 && mb_strlen($title) <= 65];
    $checks[] = ['label' => 'Meta description entre 120 et 160 caractères', 'ok' => mb_strlen($meta) >= 120 && mb_strlen($meta) <= 160];
    $checks[] = ['label' => 'H1 clair et localisé', 'ok' => mb_strlen($h1) >= 25 && ($focus === '' || str_contains(mb_strtolower($h1), $focus) || str_contains(mb_strtolower($h1), mb_strtolower((string) ($page['city'] ?? ''))))];
    $checks[] = ['label' => 'Contenu éditorial d’au moins 500 mots', 'ok' => $wordCount >= 500];
    $checks[] = ['label' => 'Ville / secteur renseigné', 'ok' => trim((string) ($page['city'] ?? '')) !== ''];
    $checks[] = ['label' => 'CTA renseigné', 'ok' => trim((string) ($page['cta_title'] ?? '')) !== '' && trim((string) ($page['cta_button_label'] ?? '')) !== ''];
    $checks[] = ['label' => 'FAQ spécifique ajoutée', 'ok' => count($faqs) >= 2];
    $checks[] = ['label' => 'Liens internes ajoutés', 'ok' => count($links) >= 2];

    $passed = count(array_filter($checks, static fn (array $check): bool => (bool) $check['ok']));
    $score = (int) round(($passed / max(1, count($checks))) * 100);
    $label = $score >= 90 ? 'Excellent' : ($score >= 75 ? 'Prêt à publier' : ($score >= 55 ? 'Correct' : 'Faible'));

    return ['score' => $score, 'label' => $label, 'checks' => $checks, 'word_count' => $wordCount];
}

function cms_seo_page_template_payload(string $template, string $city): array
{
    $city = trim($city) !== '' ? trim($city) : 'Auxois-Morvan';
    $slugCity = trim(cms_slugify($city), '/');
    $base = [
        'page_type' => 'local',
        'page_key' => null,
        'is_indexable' => 1,
        'city' => $city,
        'seo_status' => 'draft',
        'seo_template' => $template,
        'hero_image' => '',
        'hero_image_alt' => 'Immobilier à ' . $city,
        'nearby_cities_json' => json_encode([], JSON_UNESCAPED_UNICODE),
        'seo_internal_links_json' => json_encode([
            ['label' => 'Estimation en ligne', 'url' => '/estimation-en-ligne'],
            ['label' => 'Contact', 'url' => '/contact'],
        ], JSON_UNESCAPED_UNICODE),
    ];

    $templates = [
        'estimation-locale' => [
            'title' => 'Estimation immobilière ' . $city . ' | Prix maison local',
            'slug' => '/estimation-immobiliere-' . $slugCity,
            'meta_description' => 'Estimation immobilière à ' . $city . ' : analyse locale, prix maison, potentiel du bien et accompagnement pour vendre au bon prix.',
            'h1' => 'Estimation immobilière à ' . $city,
            'hero_title' => 'Faire estimer un bien à ' . $city . ' avec une lecture locale',
            'hero_subtitle' => '<p>Obtenez un avis de valeur utile, fondé sur les références, l’état du bien, le secteur et la demande réelle autour de ' . cms_h($city) . '.</p>',
            'intro_html' => '<p>Une estimation immobilière à ' . cms_h($city) . ' doit aller au-delà d’une moyenne de prix. Elle doit intégrer l’emplacement, le type de bien, les travaux, les extérieurs, les dépendances et les profils d’acquéreurs actifs.</p>',
            'sections_json' => json_encode([['eyebrow' => 'Méthode', 'title' => 'Ce que nous analysons à ' . $city, 'text' => '<p>Nous comparons votre bien avec des références cohérentes, puis nous ajustons l’analyse selon son état, son potentiel, son environnement et votre calendrier de vente.</p>', 'image' => '', 'imageAlt' => '', 'buttonLabel' => '', 'buttonUrl' => '', 'items' => ['Références comparables', 'État et travaux', 'Demande locale', 'Stratégie de prix'], 'stats' => []]], JSON_UNESCAPED_UNICODE),
            'cta_title' => 'Vous souhaitez connaître la valeur de votre bien à ' . $city . ' ?',
            'cta_text' => '<p>Transmettez les premières informations et recevez un retour clair pour cadrer votre projet.</p>',
            'cta_button_label' => 'Demander une estimation',
            'cta_button_url' => '/estimation-en-ligne?ville=' . rawurlencode($city),
            'local_page_type' => 'estimation-immobiliere',
            'seo_intent' => 'estimation',
            'seo_focus_keyword' => 'estimation immobilière ' . $city,
            'seo_secondary_keywords' => 'prix maison ' . $city . "\nestimation maison " . $city . "\nimmobilier " . $city,
            'local_advantages_json' => json_encode(['Analyse du marché local', 'Lecture de la typologie du bien', 'Conseil de prix avant mise en vente'], JSON_UNESCAPED_UNICODE),
            'seo_faq_json' => json_encode([
                ['question' => 'Comment obtenir une estimation immobilière à ' . $city . ' ?', 'answer' => 'Il faut analyser le bien, son emplacement, son état, les références comparables et la demande actuelle sur le secteur.'],
                ['question' => 'Une estimation en ligne suffit-elle ?', 'answer' => 'Elle donne un premier repère, mais un avis de valeur local permet d’affiner le prix selon les caractéristiques réelles du bien.'],
            ], JSON_UNESCAPED_UNICODE),
        ],
        'vendre-maison' => [
            'title' => 'Vendre maison ' . $city . ' | Estimation et stratégie locale',
            'slug' => '/vendre-maison-' . $slugCity,
            'meta_description' => 'Vendre une maison à ' . $city . ' : estimation locale, stratégie de prix, valorisation du bien et accompagnement jusqu’à la signature.',
            'h1' => 'Vendre une maison à ' . $city,
            'hero_title' => 'Vendre votre maison à ' . $city . ' avec une stratégie claire',
            'hero_subtitle' => '<p>Prix, présentation, diffusion et suivi des acquéreurs : chaque étape doit être adaptée au marché immobilier local.</p>',
            'intro_html' => '<p>Pour vendre une maison à ' . cms_h($city) . ', le bon positionnement repose sur une estimation argumentée, une présentation lisible du bien et une qualification sérieuse des acquéreurs.</p>',
            'sections_json' => json_encode([['eyebrow' => 'Vente', 'title' => 'Préparer une mise en vente efficace', 'text' => '<p>Nous cadrons le prix, les points forts, les réserves éventuelles et les messages à mettre en avant pour donner confiance aux acheteurs.</p>', 'image' => '', 'imageAlt' => '', 'buttonLabel' => '', 'buttonUrl' => '', 'items' => ['Estimation avant vente', 'Valorisation du bien', 'Qualification des visites', 'Suivi des offres'], 'stats' => []]], JSON_UNESCAPED_UNICODE),
            'cta_title' => 'Vous préparez une vente à ' . $city . ' ?',
            'cta_text' => '<p>Parlons de votre calendrier, du prix attendu et de la meilleure façon de présenter votre maison.</p>',
            'cta_button_label' => 'Préparer ma vente',
            'cta_button_url' => '/contact',
            'local_page_type' => 'vendre-maison',
            'seo_intent' => 'vente',
            'seo_focus_keyword' => 'vendre maison ' . $city,
            'seo_secondary_keywords' => 'vente maison ' . $city . "\nestimation avant vente " . $city . "\nimmobilier " . $city,
            'local_advantages_json' => json_encode(['Stratégie de prix', 'Valorisation des extérieurs et dépendances', 'Accompagnement jusqu’à la signature'], JSON_UNESCAPED_UNICODE),
            'seo_faq_json' => json_encode([
                ['question' => 'Comment vendre une maison à ' . $city . ' au bon prix ?', 'answer' => 'Il faut croiser les références locales, l’état du bien, les extérieurs, les travaux et la demande active.'],
                ['question' => 'Pourquoi faire estimer avant de vendre ?', 'answer' => 'Une estimation limite le risque d’un prix trop haut qui bloque les visites ou trop bas qui fragilise votre projet.'],
            ], JSON_UNESCAPED_UNICODE),
        ],
    ];

    $fallback = [
        'title' => 'Immobilier ' . $city . ' | Marché local et conseils',
        'slug' => '/immobilier-' . $slugCity,
        'meta_description' => 'Immobilier à ' . $city . ' : marché local, estimation, vente, typologies de biens et conseils pour réussir votre projet immobilier.',
        'h1' => 'Immobilier à ' . $city,
        'hero_title' => 'Comprendre l’immobilier à ' . $city,
        'hero_subtitle' => '<p>Une page locale pour analyser le marché, les biens recherchés et les repères utiles avant une vente ou une estimation.</p>',
        'intro_html' => '<p>Le marché immobilier à ' . cms_h($city) . ' varie selon la localisation précise, l’état du bien, les extérieurs, les accès et le profil des acquéreurs.</p>',
        'sections_json' => json_encode([['eyebrow' => 'Marché local', 'title' => 'Repères immobiliers à ' . $city, 'text' => '<p>Cette page synthétise les éléments qui influencent la valeur et la demande locale.</p>', 'image' => '', 'imageAlt' => '', 'buttonLabel' => '', 'buttonUrl' => '', 'items' => ['Typologies recherchées', 'Secteurs à comparer', 'Demande locale'], 'stats' => []]], JSON_UNESCAPED_UNICODE),
        'cta_title' => 'Un projet immobilier à ' . $city . ' ?',
        'cta_text' => '<p>Contactez-nous pour obtenir une lecture locale adaptée à votre bien.</p>',
        'cta_button_label' => 'Nous contacter',
        'cta_button_url' => '/contact',
        'local_page_type' => 'marche-local',
        'seo_intent' => 'marche-local',
        'seo_focus_keyword' => 'immobilier ' . $city,
        'seo_secondary_keywords' => 'prix maison ' . $city . "\nestimation " . $city . "\nvendre maison " . $city,
        'local_advantages_json' => json_encode(['Lecture locale', 'Repères de prix', 'Conseil de stratégie'], JSON_UNESCAPED_UNICODE),
        'seo_faq_json' => json_encode([
            ['question' => 'Comment évolue l’immobilier à ' . $city . ' ?', 'answer' => 'Le marché dépend de l’offre, de la demande, des typologies de biens, des accès et de l’état général des maisons.'],
            ['question' => 'Quels critères influencent le prix ?', 'answer' => 'L’emplacement, les surfaces, les extérieurs, les travaux et les références comparables sont déterminants.'],
        ], JSON_UNESCAPED_UNICODE),
    ];

    return cms_blank_page(array_merge($base, $templates[$template] ?? $fallback));
}

function cms_seo_recommended_pages(): array
{
    $existing = [];
    foreach (cms_local_pages() as $page) {
        $existing[(string) ($page['slug'] ?? '')] = true;
    }

    $recommendations = [];
    foreach (cms_seo_local_cities() as $index => $city) {
        foreach (['estimation-locale', 'vendre-maison'] as $template) {
            $payload = cms_seo_page_template_payload($template, $city);
            $slug = (string) $payload['slug'];
            if (!isset($existing[$slug])) {
                $recommendations[] = [
                    'city' => $city,
                    'template' => $template,
                    'title' => (string) $payload['title'],
                    'slug' => $slug,
                    'priority' => $index < 10 ? 'Haute' : 'Normale',
                ];
            }
        }
    }

    return array_slice($recommendations, 0, 12);
}

function cms_duplicate_local_page(int $id): int
{
    $page = cms_local_page($id);
    unset($page['id'], $page['created_at'], $page['updated_at'], $page['published_at']);
    $page['title'] = 'Copie - ' . (string) $page['title'];
    $page['slug'] = rtrim((string) $page['slug'], '/') . '-copie-' . date('His');
    $page['status'] = 'draft';
    $page['seo_status'] = 'draft';

    return cms_save_page(array_merge(cms_blank_page(), $page), null);
}

function cms_json_objects(string $json): array
{
    $decoded = json_decode($json, true);
    return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
}

function cms_page_payload_from_request(string $pageType, ?string $pageKey = null): array
{
    cms_ensure_page_seo_columns();

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

    $seoIntent = trim((string) ($_POST['seo_intent'] ?? 'estimation'));
    if (!isset(cms_seo_page_intents()[$seoIntent])) {
        $seoIntent = 'autre';
    }
    $seoStatus = trim((string) ($_POST['seo_status'] ?? 'draft'));
    if (!isset(cms_seo_page_statuses()[$seoStatus])) {
        $seoStatus = 'draft';
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
        'seo_intent' => $seoIntent,
        'seo_status' => $seoStatus,
        'seo_focus_keyword' => trim((string) ($_POST['seo_focus_keyword'] ?? '')),
        'seo_secondary_keywords' => trim((string) ($_POST['seo_secondary_keywords'] ?? '')),
        'seo_template' => trim((string) ($_POST['seo_template'] ?? '')),
        'seo_notes' => trim((string) ($_POST['seo_notes'] ?? '')),
        'seo_faq_json' => json_encode(cms_parse_pairs((string) ($_POST['seo_faq'] ?? ''), 'question', 'answer'), JSON_UNESCAPED_UNICODE),
        'seo_internal_links_json' => json_encode(cms_parse_pairs((string) ($_POST['seo_internal_links'] ?? ''), 'label', 'url'), JSON_UNESCAPED_UNICODE),
        'seo_is_primary' => isset($_POST['seo_is_primary']) ? 1 : 0,
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
    cms_ensure_page_seo_columns();

    $pdo = cms_db();
    $publishedAt = $payload['status'] === 'published' ? date('Y-m-d H:i:s') : null;

    if ($id === null) {
        $statement = $pdo->prepare(
            'INSERT INTO cms_pages (
                page_type, page_key, slug, title, meta_description, is_indexable, h1, hero_title,
                hero_subtitle, hero_image, hero_image_alt, intro_html, sections_json, cta_title,
                     cta_text, cta_button_label, cta_button_url, city, local_page_type,
                     local_advantages_json, nearby_cities_json, seo_intent, seo_status, seo_focus_keyword,
                     seo_secondary_keywords, seo_template, seo_notes, seo_faq_json, seo_internal_links_json, seo_is_primary,
                     status, published_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
            $payload['seo_intent'] ?: null,
            $payload['seo_status'] ?: 'draft',
            $payload['seo_focus_keyword'] ?: null,
            $payload['seo_secondary_keywords'] ?: null,
            $payload['seo_template'] ?: null,
            $payload['seo_notes'] ?: null,
            $payload['seo_faq_json'],
            $payload['seo_internal_links_json'],
            (int) ($payload['seo_is_primary'] ?? 0),
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
                local_advantages_json = ?, nearby_cities_json = ?, seo_intent = ?, seo_status = ?, seo_focus_keyword = ?,
                seo_secondary_keywords = ?, seo_template = ?, seo_notes = ?, seo_faq_json = ?, seo_internal_links_json = ?, seo_is_primary = ?, status = ?,
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
        $payload['seo_intent'] ?: null,
        $payload['seo_status'] ?: 'draft',
        $payload['seo_focus_keyword'] ?: null,
        $payload['seo_secondary_keywords'] ?: null,
        $payload['seo_template'] ?: null,
        $payload['seo_notes'] ?: null,
        $payload['seo_faq_json'],
        $payload['seo_internal_links_json'],
        (int) ($payload['seo_is_primary'] ?? 0),
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

            foreach (['uploads/cms', 'uploads'] as $legacyDirectory) {
                foreach (glob(cms_config()['root'] . '/' . $legacyDirectory . '/*' . $legacyName) ?: [] as $match) {
                    if (is_file($match)) {
                        $candidates[] = '/' . ltrim(substr($match, strlen(cms_config()['root'])), '/');
                    }
                }
            }
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
            request_type VARCHAR(50) NOT NULL DEFAULT 'estimation',
            property_type VARCHAR(100) NOT NULL,
            room_count VARCHAR(100) NOT NULL,
            property_condition VARCHAR(100) NOT NULL,
            living_surface VARCHAR(100) NOT NULL,
            land_surface VARCHAR(100) NOT NULL,
            occupancy_intent VARCHAR(150) DEFAULT NULL,
            commune VARCHAR(150) NOT NULL,
            postal_code VARCHAR(20) DEFAULT NULL,
            address_details TEXT NOT NULL,
            goal VARCHAR(150) NOT NULL,
            owner_situation VARCHAR(150) DEFAULT NULL,
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
            KEY idx_cms_estimation_requests_type (request_type),
            KEY idx_cms_estimation_requests_status (status),
            KEY idx_cms_estimation_requests_commune (commune),
            KEY idx_cms_estimation_requests_created_at (created_at),
            KEY idx_cms_estimation_requests_utm_campaign (utm_campaign)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo = cms_db();
    $statement = $pdo->prepare(
        "SELECT COLUMN_NAME
           FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cms_estimation_requests'
            AND COLUMN_NAME IN ('request_type', 'occupancy_intent', 'owner_situation')"
    );
    $statement->execute();
    $existing = array_flip(array_column($statement->fetchAll(), 'COLUMN_NAME'));

    if (!isset($existing['request_type'])) {
        $pdo->exec("ALTER TABLE cms_estimation_requests ADD COLUMN request_type VARCHAR(50) NOT NULL DEFAULT 'estimation' AFTER id");
        $pdo->exec("ALTER TABLE cms_estimation_requests ADD KEY idx_cms_estimation_requests_type (request_type)");
    }

    if (!isset($existing['occupancy_intent'])) {
        $pdo->exec("ALTER TABLE cms_estimation_requests ADD COLUMN occupancy_intent VARCHAR(150) DEFAULT NULL AFTER land_surface");
    }

    if (!isset($existing['owner_situation'])) {
        $pdo->exec("ALTER TABLE cms_estimation_requests ADD COLUMN owner_situation VARCHAR(150) DEFAULT NULL AFTER goal");
    }

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
    $requestType = trim((string) ($_POST['request_type'] ?? 'estimation'));
    if (!in_array($requestType, ['estimation', 'viager'], true)) {
        $requestType = 'estimation';
    }

    return [
        'request_type' => $requestType,
        'property_type' => trim((string) ($_POST['property_type'] ?? '')),
        'room_count' => trim((string) ($_POST['room_count'] ?? '')),
        'property_condition' => trim((string) ($_POST['property_condition'] ?? '')),
        'living_surface' => trim((string) ($_POST['living_surface'] ?? '')),
        'land_surface' => trim((string) ($_POST['land_surface'] ?? '')),
        'occupancy_intent' => trim((string) ($_POST['occupancy_intent'] ?? '')),
        'commune' => trim((string) ($_POST['commune'] ?? '')),
        'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
        'address_details' => trim((string) ($_POST['address_details'] ?? '')),
        'goal' => trim((string) ($_POST['goal'] ?? '')),
        'owner_situation' => trim((string) ($_POST['owner_situation'] ?? '')),
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
    $isViager = (string) ($payload['request_type'] ?? 'estimation') === 'viager';

    $requiredFields = $isViager
        ? ['property_type', 'room_count', 'living_surface', 'occupancy_intent', 'goal', 'commune', 'address_details', 'owner_situation', 'project_timeline', 'first_name', 'last_name', 'email', 'phone']
        : ['property_type', 'room_count', 'property_condition', 'living_surface', 'land_surface', 'commune', 'address_details', 'goal', 'project_timeline', 'first_name', 'last_name', 'email', 'phone'];

    foreach ($requiredFields as $field) {
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
        $errors[] = $isViager
            ? 'Vous devez accepter d’être recontacté au sujet de votre demande d’étude viager.'
            : 'Vous devez accepter d’être recontacté au sujet de votre demande d’estimation.';
    }

    return array_values(array_unique($errors));
}

function cms_save_estimation_request(array $payload): int
{
    cms_ensure_estimation_requests_table();

    $statement = cms_db()->prepare(
        'INSERT INTO cms_estimation_requests (
            request_type, property_type, room_count, property_condition, living_surface, land_surface, occupancy_intent,
            commune, postal_code, address_details, goal, owner_situation, project_timeline,
            first_name, last_name, email, phone, contact_consent, outside_area,
            status, internal_notes, source, utm_source, utm_campaign, utm_content, utm_medium, origin_page
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $payload['request_type'] ?? 'estimation',
        $payload['property_type'],
        $payload['room_count'],
        $payload['property_condition'],
        $payload['living_surface'],
        $payload['land_surface'],
        ($payload['occupancy_intent'] ?? '') !== '' ? $payload['occupancy_intent'] : null,
        $payload['commune'],
        $payload['postal_code'] !== '' ? $payload['postal_code'] : null,
        $payload['address_details'],
        $payload['goal'],
        ($payload['owner_situation'] ?? '') !== '' ? $payload['owner_situation'] : null,
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

    $requestType = trim((string) ($filters['request_type'] ?? ''));
    if ($requestType !== '' && in_array($requestType, ['estimation', 'viager'], true)) {
        $where[] = 'request_type = ?';
        $params[] = $requestType;
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
    $isViager = (string) ($request['request_type'] ?? 'estimation') === 'viager';

    if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $adminBody = ($isViager ? "Nouvelle demande d'étude viager\n\n" : "Nouvelle demande d'estimation en ligne\n\n")
            . "Nom : {$fullName}\n"
            . "Email : {$request['email']}\n"
            . "Téléphone : {$request['phone']}\n"
            . "Commune : {$commune}\n"
            . "Type de bien : {$request['property_type']}\n"
            . ($isViager ? "Souhait logement : " . (($request['occupancy_intent'] ?? '') !== '' ? $request['occupancy_intent'] : 'n/a') . "\n" : '')
            . "Objectif : {$request['goal']}\n"
            . ($isViager ? "Situation : " . (($request['owner_situation'] ?? '') !== '' ? $request['owner_situation'] : 'n/a') . "\n" : '')
            . "Délai : {$request['project_timeline']}\n"
            . "Campagne : " . (($request['utm_campaign'] ?? '') !== '' ? $request['utm_campaign'] : 'n/a') . "\n"
            . "Source : " . (($request['utm_source'] ?? '') !== '' ? $request['utm_source'] : 'n/a') . "\n";
        @mail($adminEmail, $isViager ? 'Nouvelle demande d’étude viager' : 'Nouvelle demande d’estimation en ligne', $adminBody, cms_mail_headers((string) $request['email']));
    }

    if (filter_var((string) ($request['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        $prospectBody = $isViager
            ? "Bonjour {$request['first_name']},\n\n"
                . "Merci pour votre demande d’étude viager concernant votre bien situé à {$commune}.\n\n"
                . "J’ai bien reçu les informations transmises. Je vais les analyser avec attention et vous recontacter sous 24h pour échanger sur les solutions adaptées à votre situation.\n\n"
                . "À très bientôt,\n\n"
                . trim((string) ($settings['mickael_name'] ?? 'Mickael Gury')) . "\n"
                . "Conseiller immobilier IAD"
            : "Bonjour {$request['first_name']},\n\n"
                . "Merci pour votre demande d’estimation concernant votre bien situé à {$commune}.\n\n"
                . "J’ai bien reçu les informations transmises. Je vais les analyser et je vous recontacterai sous 24h pour vous donner un premier avis de valeur.\n\n"
                . "À très bientôt,\n\n"
                . trim((string) ($settings['mickael_name'] ?? 'Mickael Gury')) . "\n"
                . "Conseiller immobilier IAD";
        @mail((string) $request['email'], $isViager ? 'Votre demande d’étude viager a bien été reçue' : 'Votre demande d’estimation a bien été reçue', $prospectBody, cms_mail_headers($adminEmail));
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

function cms_ensure_estimation_events_table(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    cms_db()->exec(
        "CREATE TABLE IF NOT EXISTS cms_estimation_events (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          event_name VARCHAR(100) NOT NULL,
          visitor_id VARCHAR(80) NOT NULL,
          session_id VARCHAR(80) DEFAULT NULL,
          step_number TINYINT UNSIGNED DEFAULT NULL,
          step_field VARCHAR(100) DEFAULT NULL,
          choice_value VARCHAR(180) DEFAULT NULL,
          page_url VARCHAR(255) DEFAULT NULL,
          referrer VARCHAR(255) DEFAULT NULL,
          utm_source VARCHAR(190) DEFAULT NULL,
          utm_medium VARCHAR(190) DEFAULT NULL,
          utm_campaign VARCHAR(190) DEFAULT NULL,
          utm_content VARCHAR(190) DEFAULT NULL,
          ip_hash CHAR(64) DEFAULT NULL,
          user_agent_hash CHAR(64) DEFAULT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_cms_estimation_events_created_at (created_at),
          KEY idx_cms_estimation_events_event_name (event_name),
          KEY idx_cms_estimation_events_visitor (visitor_id),
          KEY idx_cms_estimation_events_step (step_number),
          KEY idx_cms_estimation_events_utm_campaign (utm_campaign)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $done = true;
}

function cms_estimation_tracking_events(): array
{
    return [
        'estimation_page_view',
        'estimation_form_started',
        'estimation_step_viewed',
        'estimation_choice_clicked',
        'estimation_step_completed',
        'estimation_next_clicked',
        'estimation_back_clicked',
        'estimation_form_submitted',
        'estimation_lead_created',
    ];
}

function cms_tracking_text(mixed $value, int $maxLength): ?string
{
    $text = trim((string) $value);
    if ($text === '') {
        return null;
    }

    return mb_substr($text, 0, $maxLength);
}

function cms_handle_estimation_tracking_request(): never
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit;
    }

    $raw = (string) file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $eventName = cms_tracking_text($payload['event_name'] ?? '', 100);
    if ($eventName === null || !in_array($eventName, cms_estimation_tracking_events(), true)) {
        http_response_code(204);
        exit;
    }

    $data = isset($payload['payload']) && is_array($payload['payload']) ? $payload['payload'] : [];
    $visitorId = cms_tracking_text($payload['visitor_id'] ?? '', 80);
    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $salt = (string) (cms_config()['db_name'] ?? 'immobilier-auxois');

    if ($visitorId === null) {
        $visitorId = hash('sha256', $ip . '|' . $userAgent . '|' . date('Y-m-d') . '|' . $salt);
    }

    $stepNumber = isset($data['step_number']) ? (int) $data['step_number'] : null;
    if ($stepNumber !== null && ($stepNumber < 1 || $stepNumber > 20)) {
        $stepNumber = null;
    }

    cms_ensure_estimation_events_table();
    $statement = cms_db()->prepare(
        'INSERT INTO cms_estimation_events (
            event_name, visitor_id, session_id, step_number, step_field, choice_value,
            page_url, referrer, utm_source, utm_medium, utm_campaign, utm_content,
            ip_hash, user_agent_hash
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $statement->execute([
        $eventName,
        $visitorId,
        cms_tracking_text($payload['session_id'] ?? null, 80),
        $stepNumber,
        cms_tracking_text($data['step_field'] ?? null, 100),
        cms_tracking_text($data['choice_value'] ?? null, 180),
        cms_tracking_text($payload['page_url'] ?? null, 255),
        cms_tracking_text($payload['referrer'] ?? null, 255),
        cms_tracking_text($payload['utm_source'] ?? ($data['utm_source'] ?? null), 190),
        cms_tracking_text($payload['utm_medium'] ?? ($data['utm_medium'] ?? null), 190),
        cms_tracking_text($payload['utm_campaign'] ?? ($data['utm_campaign'] ?? null), 190),
        cms_tracking_text($payload['utm_content'] ?? ($data['utm_content'] ?? null), 190),
        $ip !== '' ? hash('sha256', $ip . '|' . $salt) : null,
        $userAgent !== '' ? hash('sha256', $userAgent . '|' . $salt) : null,
    ]);

    http_response_code(204);
    exit;
}

function cms_estimation_analytics_periods(): array
{
    return [
        '24h' => ['label' => '24h', 'interval' => '24 HOUR'],
        '7d' => ['label' => '7j', 'interval' => '7 DAY'],
        '30d' => ['label' => '30j', 'interval' => '30 DAY'],
    ];
}

function cms_estimation_analytics_period_key(?string $value): string
{
    $periods = cms_estimation_analytics_periods();
    return isset($periods[(string) $value]) ? (string) $value : '7d';
}

function cms_estimation_analytics_stats(string $periodKey): array
{
    cms_ensure_estimation_events_table();
    $periods = cms_estimation_analytics_periods();
    $period = $periods[$periodKey] ?? $periods['7d'];
    $interval = $period['interval'];
    $where = "created_at >= DATE_SUB(NOW(), INTERVAL {$interval})";

    $summary = cms_db()->query(
        "SELECT
          COUNT(*) AS total_events,
          COUNT(DISTINCT visitor_id) AS visitors,
          SUM(event_name = 'estimation_page_view') AS page_views,
          SUM(event_name = 'estimation_choice_clicked') AS choice_clicks,
          SUM(event_name = 'estimation_step_completed') AS step_completions,
          SUM(event_name = 'estimation_form_submitted') AS form_submits
        FROM cms_estimation_events
        WHERE {$where}"
    )->fetch() ?: [];

    $leadStatement = cms_db()->query("SELECT COUNT(*) AS total FROM cms_estimation_requests WHERE {$where}");
    $leadCount = (int) (($leadStatement->fetch()['total'] ?? 0));

    $funnelRows = cms_db()->query(
        "SELECT step_number, COUNT(DISTINCT visitor_id) AS visitors, COUNT(*) AS events
           FROM cms_estimation_events
          WHERE {$where} AND event_name = 'estimation_step_viewed' AND step_number IS NOT NULL
          GROUP BY step_number
          ORDER BY step_number ASC"
    )->fetchAll();

    $choiceRows = cms_db()->query(
        "SELECT step_field, choice_value, COUNT(*) AS total
           FROM cms_estimation_events
          WHERE {$where} AND event_name = 'estimation_choice_clicked' AND choice_value IS NOT NULL
          GROUP BY step_field, choice_value
          ORDER BY total DESC
          LIMIT 6"
    )->fetchAll();

    $sourceRows = cms_db()->query(
        "SELECT COALESCE(NULLIF(utm_source, ''), 'direct / inconnu') AS source, COUNT(DISTINCT visitor_id) AS visitors
           FROM cms_estimation_events
          WHERE {$where} AND event_name = 'estimation_page_view'
          GROUP BY source
          ORDER BY visitors DESC
          LIMIT 4"
    )->fetchAll();

    $visitors = (int) ($summary['visitors'] ?? 0);
    $pageViews = (int) ($summary['page_views'] ?? 0);

    return [
        'period' => $period,
        'visitors' => $visitors,
        'page_views' => $pageViews,
        'choice_clicks' => (int) ($summary['choice_clicks'] ?? 0),
        'step_completions' => (int) ($summary['step_completions'] ?? 0),
        'form_submits' => (int) ($summary['form_submits'] ?? 0),
        'leads' => $leadCount,
        'conversion_rate' => $visitors > 0 ? round(($leadCount / $visitors) * 100) : 0,
        'funnel' => $funnelRows,
        'choices' => $choiceRows,
        'sources' => $sourceRows,
    ];
}

function cms_ensure_seo_tables(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo = cms_db();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS cms_seo_keywords (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          keyword VARCHAR(255) NOT NULL,
          city VARCHAR(150) DEFAULT NULL,
          intent VARCHAR(80) NOT NULL DEFAULT 'local',
          target_url VARCHAR(255) DEFAULT NULL,
          target_page_id INT UNSIGNED DEFAULT NULL,
          priority TINYINT UNSIGNED NOT NULL DEFAULT 2,
          status ENUM('active','paused','to-create','to-optimize') NOT NULL DEFAULT 'active',
          action_status VARCHAR(60) DEFAULT NULL,
          notes LONGTEXT DEFAULT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uniq_cms_seo_keywords_keyword (keyword),
          KEY idx_cms_seo_keywords_city (city),
                    KEY idx_cms_seo_keywords_target_page (target_page_id),
                    KEY idx_cms_seo_keywords_action_status (action_status),
          KEY idx_cms_seo_keywords_status (status),
          KEY idx_cms_seo_keywords_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS cms_seo_measurements (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          keyword_id INT UNSIGNED NOT NULL,
          source ENUM('manual','csv','search_console') NOT NULL DEFAULT 'manual',
          period_start DATE NOT NULL,
          period_end DATE NOT NULL,
          clicks INT UNSIGNED NOT NULL DEFAULT 0,
          impressions INT UNSIGNED NOT NULL DEFAULT 0,
          ctr DECIMAL(8,4) NOT NULL DEFAULT 0,
          position DECIMAL(8,2) DEFAULT NULL,
          best_page VARCHAR(255) DEFAULT NULL,
          raw_json LONGTEXT DEFAULT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_cms_seo_measurements_keyword_date (keyword_id, period_end),
          KEY idx_cms_seo_measurements_source (source),
          CONSTRAINT fk_cms_seo_measurements_keyword
            FOREIGN KEY (keyword_id) REFERENCES cms_seo_keywords(id)
            ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS cms_seo_oauth_tokens (
          provider VARCHAR(60) NOT NULL,
          access_token LONGTEXT DEFAULT NULL,
          refresh_token LONGTEXT DEFAULT NULL,
          token_type VARCHAR(40) DEFAULT NULL,
          scope LONGTEXT DEFAULT NULL,
          expires_at DATETIME DEFAULT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS cms_seo_ai_analyses (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          analysis_type VARCHAR(60) NOT NULL DEFAULT 'overview',
          model VARCHAR(80) DEFAULT NULL,
          result_json LONGTEXT NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_cms_seo_ai_analyses_type_date (analysis_type, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $statement = $pdo->prepare(
        "SELECT COLUMN_NAME
           FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cms_seo_keywords'
            AND COLUMN_NAME IN ('target_page_id', 'action_status')"
    );
    $statement->execute();
    $existing = array_flip(array_column($statement->fetchAll(), 'COLUMN_NAME'));
    if (!isset($existing['target_page_id'])) {
        $pdo->exec('ALTER TABLE cms_seo_keywords ADD COLUMN target_page_id INT UNSIGNED DEFAULT NULL AFTER target_url');
        $pdo->exec('ALTER TABLE cms_seo_keywords ADD KEY idx_cms_seo_keywords_target_page (target_page_id)');
    }
    if (!isset($existing['action_status'])) {
        $pdo->exec('ALTER TABLE cms_seo_keywords ADD COLUMN action_status VARCHAR(60) DEFAULT NULL AFTER status');
        $pdo->exec('ALTER TABLE cms_seo_keywords ADD KEY idx_cms_seo_keywords_action_status (action_status)');
    }

    $done = true;
}

function cms_seo_local_cities(): array
{
    return [
        'Arnay-le-Duc',
        'Pouilly-en-Auxois',
        'Autun',
        'Dijon',
        'Beaune',
        'Saulieu',
        'Nolay',
        'Bligny-sur-Ouche',
        'Liernais',
        'Épinac',
        'Vitteaux',
        'Sombernon',
        'Précy-sous-Thil',
        'Créancey',
        'Sainte-Sabine',
        'Chailly-sur-Armançon',
        'Lacanche',
        'La Rochepot',
        'Mont-Saint-Jean',
        'Alligny-en-Morvan',
    ];
}

function cms_seo_city_keyword_variants(string $city): array
{
    $variants = [$city, mb_strtolower(str_replace('-', ' ', $city))];
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $city) ?: $city;
    $variants[] = mb_strtolower($ascii);
    $variants[] = mb_strtolower(str_replace('-', ' ', $ascii));

    return array_values(array_unique(array_filter(array_map('trim', $variants), static fn ($value) => $value !== '')));
}

function cms_seo_detection_cities(): array
{
    return array_values(array_unique(array_merge(cms_seo_local_cities(), [
        'Mimeure',
        'Semur-en-Auxois',
        'Auxois',
        'Morvan',
        'Auxois-Morvan',
    ])));
}

function cms_seo_normalize_text(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?: $value);
}

function cms_detect_seo_keyword_city(string $keyword): ?string
{
    $normalized = ' ' . cms_seo_normalize_text($keyword) . ' ';
    foreach (cms_seo_detection_cities() as $city) {
        foreach (cms_seo_city_keyword_variants($city) as $variant) {
            $variant = cms_seo_normalize_text($variant);
            if ($variant !== '' && str_contains($normalized, ' ' . $variant . ' ')) {
                return $city;
            }
        }
    }

    return null;
}

function cms_detect_seo_keyword_intent(string $keyword): string
{
    $text = cms_seo_normalize_text($keyword);
    $patterns = [
        'fonds-commerce' => ['fonds commerce', 'fond de commerce', 'murs commerciaux', 'local commercial', 'commerce a vendre'],
        'viager' => ['viager', 'bouquet', 'rente viagere'],
        'estimation' => ['estimation', 'estimer', 'prix maison', 'valeur maison', 'avis de valeur', 'combien vaut'],
        'vente' => ['vendre', 'vente maison', 'mettre en vente', 'vendre maison'],
        'marche-local' => ['marche immobilier', 'prix immobilier', 'immobilier'],
        'guide-pratique' => ['comment', 'guide', 'conseil', 'notaire', 'diagnostic', 'demarche'],
        'agence' => ['agence immobiliere', 'agent immobilier', 'conseiller immobilier', 'mandataire immobilier'],
        'achat' => ['acheter', 'achat maison', 'maison a vendre', 'bien a vendre'],
    ];

    foreach ($patterns as $intent => $needles) {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return $intent;
            }
        }
    }

    return 'autre';
}

function cms_seo_intent_label(string $intent): string
{
    $labels = cms_seo_page_intents() + ['agence' => 'Agence', 'achat' => 'Achat'];
    return $labels[$intent] ?? 'Autre';
}

function cms_seo_template_for_intent(string $intent): string
{
    return match ($intent) {
        'vente' => 'vendre-maison',
        'viager' => 'viager',
        'fonds-commerce' => 'fonds-de-commerce',
        'guide-pratique' => 'guide-pratique',
        'marche-local', 'agence', 'achat' => 'page-pilier',
        default => 'estimation-locale',
    };
}

function cms_seo_keyword_action_statuses(): array
{
    return [
        'existing' => 'Page existante',
        'reinforce' => 'À renforcer',
        'opportunity' => 'Opportunité',
        'associate' => 'À associer',
        'create' => 'Nouvelle page conseillée',
        'ignore' => 'Ignorer',
    ];
}

function cms_seo_keyword_statuses(): array
{
    return [
        'active' => 'Actif',
        'to-create' => 'Page à créer',
        'to-optimize' => 'À optimiser',
        'paused' => 'En pause',
    ];
}

function cms_seo_priority_label(int $priority): string
{
    return match ($priority) {
        1 => 'Haute',
        3 => 'Basse',
        default => 'Normale',
    };
}

function cms_seo_target_url_for_city(string $city, string $intent): string
{
    $slug = trim(cms_slugify($city), '/');

    return match ($intent) {
        'estimation' => '/estimation-immobiliere-' . $slug,
        'vente' => '/vendre-maison-' . $slug,
        'agence' => '/agence-immobiliere-' . $slug,
        default => '/immobilier-' . $slug,
    };
}

function cms_seo_keyword_opportunity(array $keyword): array
{
    $impressions = (int) ($keyword['impressions'] ?? 0);
    $clicks = (int) ($keyword['clicks'] ?? 0);
    $position = $keyword['position'] !== null ? (float) $keyword['position'] : null;
    $priority = (int) ($keyword['priority'] ?? 2);
    $points = 0;

    if ($impressions >= 200) {
        $points += 3;
    } elseif ($impressions >= 50) {
        $points += 2;
    } elseif ($impressions >= 10) {
        $points += 1;
    }
    if ($position !== null && $position >= 4 && $position <= 20) {
        $points += 3;
    } elseif ($position !== null && $position > 20 && $position <= 50) {
        $points += 1;
    }
    if ($clicks === 0 && $impressions >= 30) {
        $points += 1;
    }
    if ($priority === 1) {
        $points += 1;
    }

    if ($points >= 5) {
        return ['label' => 'Haute', 'tone' => 'improve', 'score' => $points];
    }
    if ($points >= 3) {
        return ['label' => 'Moyenne', 'tone' => 'review', 'score' => $points];
    }

    return ['label' => 'Faible', 'tone' => 'neutral', 'score' => $points];
}

function cms_seo_match_keyword_to_page(array $keyword, array $pages): array
{
    $keywordText = (string) ($keyword['keyword'] ?? '');
    $detectedCity = cms_detect_seo_keyword_city($keywordText);
    $detectedIntent = cms_detect_seo_keyword_intent($keywordText);
    $city = trim((string) ($keyword['city'] ?? '')) ?: $detectedCity;
    $intent = trim((string) ($keyword['intent'] ?? ''));
    if ($intent === '' || $intent === 'local' || $intent === 'immobilier') {
        $intent = $detectedIntent;
    }
    if ($intent === 'agence' || $intent === 'achat') {
        $intent = 'marche-local';
    }
    $targetPageId = (int) ($keyword['target_page_id'] ?? 0);
    $targetUrl = trim((string) ($keyword['target_url'] ?? '')) ?: trim((string) ($keyword['best_page'] ?? ''));
    $manualAction = trim((string) ($keyword['action_status'] ?? ''));
    $opportunity = cms_seo_keyword_opportunity($keyword);
    $matched = null;
    $reason = 'Aucune page cible fiable détectée.';
    $confidence = 'faible';

    foreach ($pages as $page) {
        if ($targetPageId > 0 && (int) ($page['id'] ?? 0) === $targetPageId) {
            $matched = $page;
            $reason = 'Association manuelle avec cette page.';
            $confidence = 'forte';
            break;
        }
    }
    if ($matched === null && $targetUrl !== '') {
        foreach ($pages as $page) {
            if ((string) ($page['slug'] ?? '') === $targetUrl || cms_absolute_url((string) ($page['slug'] ?? '/')) === $targetUrl) {
                $matched = $page;
                $reason = 'URL cible déjà connue dans Search Console.';
                $confidence = 'forte';
                break;
            }
        }
    }
    if ($matched === null && $city !== null && $city !== '') {
        $candidates = array_values(array_filter($pages, static function (array $page) use ($city, $intent): bool {
            return cms_seo_normalize_text((string) ($page['city'] ?? '')) === cms_seo_normalize_text($city)
                && (string) ($page['seo_intent'] ?? '') === $intent;
        }));
        usort($candidates, static function (array $left, array $right): int {
            $primary = ((int) ($right['seo_is_primary'] ?? 0)) <=> ((int) ($left['seo_is_primary'] ?? 0));
            if ($primary !== 0) {
                return $primary;
            }
            return ((int) ($right['seo_score'] ?? 0)) <=> ((int) ($left['seo_score'] ?? 0));
        });
        if ($candidates !== []) {
            $matched = $candidates[0];
            $reason = 'Même commune et même intention SEO.';
            $confidence = !empty($matched['seo_is_primary']) ? 'forte' : 'moyenne';
        }
    }

    if ($manualAction === 'ignore' || (string) ($keyword['status'] ?? '') === 'paused') {
        $action = ['key' => 'ignore', 'label' => 'Ignorer', 'tone' => 'neutral', 'detail' => 'Mot-clé mis de côté pour le pilotage SEO.'];
    } elseif ($matched !== null) {
        $score = cms_seo_page_score($matched);
        $position = $keyword['position'] !== null ? (float) $keyword['position'] : null;
        if ((int) $score['score'] < 75 || $manualAction === 'reinforce' || (int) ($keyword['impressions'] ?? 0) >= 30 && ($position === null || $position > 8)) {
            $action = ['key' => 'reinforce', 'label' => 'Renforcer la page', 'tone' => 'improve', 'detail' => 'Ajouter ce mot-clé, enrichir le contenu et améliorer les signaux SEO de la page cible.'];
        } else {
            $action = ['key' => 'existing', 'label' => 'Page existante', 'tone' => 'ready', 'detail' => 'La page cible existe déjà : suivre les performances et ajuster si besoin.'];
        }
    } elseif ($city !== null && $city !== '' && $intent !== 'autre') {
        $action = ['key' => 'create', 'label' => 'Créer une page', 'tone' => 'review', 'detail' => 'Aucune page cible proche : vraie opportunité de nouvelle page locale.'];
    } else {
        $action = ['key' => 'associate', 'label' => 'Associer manuellement', 'tone' => 'neutral', 'detail' => 'Intention ou commune incertaine : choisir une page cible avant création.'];
    }

    return [
        'city' => $city,
        'intent' => $intent,
        'intent_label' => cms_seo_intent_label($intent),
        'page' => $matched,
        'confidence' => $confidence,
        'reason' => $reason,
        'action' => $action,
        'opportunity' => $opportunity,
        'target_url' => $matched !== null ? (string) ($matched['slug'] ?? '') : ($city ? cms_seo_target_url_for_city($city, $intent) : ''),
    ];
}

function cms_recommend_seo_action(array $keyword, array $existingPages): array
{
    $insight = cms_seo_match_keyword_to_page($keyword, $existingPages);
    $actionKey = (string) ($insight['action']['key'] ?? 'associate');
    $page = $insight['page'] ?? null;
    $mappedAction = match ($actionKey) {
        'reinforce', 'existing' => 'improve_existing_page',
        'create' => 'create_page',
        'ignore' => 'ignore',
        default => 'associate_manually',
    };

    return [
        'action' => $mappedAction,
        'targetPageId' => is_array($page) ? (int) ($page['id'] ?? 0) : null,
        'suggestedPageTitle' => $mappedAction === 'create_page' ? 'Page ' . cms_seo_intent_label((string) ($insight['intent'] ?? 'autre')) . ' ' . (string) ($insight['city'] ?? '') : null,
        'reason' => (string) ($insight['action']['detail'] ?? $insight['reason'] ?? ''),
        'duplicateRisk' => is_array($page) ? 'medium' : 'low',
        'insight' => $insight,
    ];
}

function cms_seo_keyword_insights(array $keywords, array $pages): array
{
    $items = [];
    foreach ($keywords as $keyword) {
        $keyword['_seo'] = cms_seo_match_keyword_to_page($keyword, $pages);
        $items[] = $keyword;
    }

    return $items;
}

function cms_seo_keyword_groups(array $keywords): array
{
    $groups = [];
    foreach ($keywords as $keyword) {
        $seo = $keyword['_seo'] ?? [];
        $key = implode('|', [(string) ($seo['city'] ?? 'Secteur'), (string) ($seo['intent'] ?? 'autre'), (string) ($seo['target_url'] ?? '')]);
        if (!isset($groups[$key])) {
            $groups[$key] = ['city' => (string) ($seo['city'] ?? 'Secteur'), 'intent' => (string) ($seo['intent_label'] ?? 'Autre'), 'target_url' => (string) ($seo['target_url'] ?? ''), 'action' => $seo['action'] ?? [], 'keywords' => [], 'impressions' => 0];
        }
        $groups[$key]['keywords'][] = $keyword;
        $groups[$key]['impressions'] += (int) ($keyword['impressions'] ?? 0);
    }
    usort($groups, static fn (array $left, array $right): int => ((int) $right['impressions']) <=> ((int) $left['impressions']));

    return array_values($groups);
}

function cms_seo_cannibalization_groups(array $pages): array
{
    $groups = [];
    foreach ($pages as $page) {
        $city = cms_seo_normalize_text((string) ($page['city'] ?? ''));
        $intent = (string) ($page['seo_intent'] ?? 'autre');
        if ($city === '' || $intent === '') {
            continue;
        }
        $groups[$city . '|' . $intent][] = $page;
    }

    return array_values(array_filter($groups, static fn (array $group): bool => count($group) > 1));
}

function cms_seo_keywords_for_page(array $page, array $keywords = []): array
{
    $keywords = $keywords !== [] ? $keywords : cms_seo_keywords();
    $pages = cms_local_pages();
    $results = [];
    foreach (cms_seo_keyword_insights($keywords, $pages) as $keyword) {
        $target = $keyword['_seo']['page'] ?? null;
        if (is_array($target) && (int) ($target['id'] ?? 0) === (int) ($page['id'] ?? 0)) {
            $results[] = $keyword;
        }
    }

    return array_slice($results, 0, 12);
}

function cms_seo_primary_page_conflicts(array $page): array
{
    if (empty($page['seo_is_primary']) || trim((string) ($page['city'] ?? '')) === '' || trim((string) ($page['seo_intent'] ?? '')) === '') {
        return [];
    }
    return array_values(array_filter(cms_local_pages(), static function (array $candidate) use ($page): bool {
        return (int) ($candidate['id'] ?? 0) !== (int) ($page['id'] ?? 0)
            && !empty($candidate['seo_is_primary'])
            && cms_seo_normalize_text((string) ($candidate['city'] ?? '')) === cms_seo_normalize_text((string) ($page['city'] ?? ''))
            && (string) ($candidate['seo_intent'] ?? '') === (string) ($page['seo_intent'] ?? '');
    }));
}

function cms_latest_seo_ai_analysis(string $type = 'overview'): ?array
{
    cms_ensure_seo_tables();
    $statement = cms_db()->prepare('SELECT * FROM cms_seo_ai_analyses WHERE analysis_type = ? ORDER BY created_at DESC, id DESC LIMIT 1');
    $statement->execute([$type]);
    $row = $statement->fetch();
    if (!$row) {
        return null;
    }
    $decoded = json_decode((string) ($row['result_json'] ?? ''), true);
    if (!is_array($decoded)) {
        return null;
    }
    $row['result'] = $decoded;

    return $row;
}

function cms_save_seo_ai_analysis(array $result, string $type = 'overview'): int
{
    cms_ensure_seo_tables();
    $statement = cms_db()->prepare('INSERT INTO cms_seo_ai_analyses (analysis_type, model, result_json) VALUES (?, ?, ?)');
    $statement->execute([
        $type,
        (string) (cms_config()['openai_model'] ?? 'gpt-5.5'),
        json_encode($result, JSON_UNESCAPED_UNICODE),
    ]);

    return (int) cms_db()->lastInsertId();
}

function cms_seo_ai_extract_json(string $text): array
{
    $trimmed = trim($text);
    $trimmed = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $trimmed) ?: $trimmed;
    $trimmed = preg_replace('/[[:cntrl:]]+/', ' ', $trimmed) ?: $trimmed;
    $decoded = json_decode($trimmed, true);
    if (!is_array($decoded)) {
        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
        }
    }
    if (!is_array($decoded)) {
        $preview = mb_substr(preg_replace('/\s+/', ' ', $trimmed) ?: $trimmed, 0, 500);
        error_log('Réponse IA SEO illisible : ' . $preview);
        throw new RuntimeException('L’analyse IA a répondu dans un format illisible. Aperçu reçu : ' . $preview);
    }

    return $decoded;
}

function cms_seo_normalize_ai_plan(array $plan): array
{
    $impactMap = ['haute' => 'high', 'forte' => 'high', 'high' => 'high', 'moyenne' => 'medium', 'medium' => 'medium', 'normale' => 'medium', 'basse' => 'low', 'faible' => 'low', 'low' => 'low'];
    $actionMap = ['améliorer' => 'improve_pages', 'ameliorer' => 'improve_pages', 'corriger' => 'improve_pages', 'créer' => 'create_pages', 'creer' => 'create_pages', 'mots-clés' => 'keyword_opportunities', 'mots cles' => 'keyword_opportunities', 'doublons' => 'duplicate_risks'];

    foreach (['weeklyPriorities', 'pagesToImprove', 'pagesToCreate', 'keywordOpportunities', 'duplicateRisks'] as $key) {
        if (!isset($plan[$key]) || !is_array($plan[$key])) {
            $plan[$key] = [];
        }
    }
    $plan['summary'] = trim((string) ($plan['summary'] ?? 'Plan SEO généré.'));

    foreach ($plan['weeklyPriorities'] as &$priority) {
        $impact = cms_seo_normalize_text((string) ($priority['impact'] ?? 'medium'));
        $priority['impact'] = $impactMap[$impact] ?? 'medium';
        $actionType = (string) ($priority['actionType'] ?? '');
        if (!in_array($actionType, ['improve_pages', 'create_pages', 'keyword_opportunities', 'duplicate_risks', 'run_ai'], true)) {
            $label = cms_seo_normalize_text((string) (($priority['actionLabel'] ?? '') . ' ' . ($priority['title'] ?? '')));
            $actionType = 'improve_pages';
            foreach ($actionMap as $needle => $mapped) {
                if (str_contains($label, $needle)) {
                    $actionType = $mapped;
                    break;
                }
            }
        }
        $priority['actionType'] = $actionType;
    }
    unset($priority);

    foreach ($plan['pagesToImprove'] as &$page) {
        $impact = cms_seo_normalize_text((string) ($page['priority'] ?? 'medium'));
        $page['priority'] = $impactMap[$impact] ?? 'medium';
        $page['recommendedActions'] = isset($page['recommendedActions']) && is_array($page['recommendedActions']) ? array_values($page['recommendedActions']) : [];
    }
    unset($page);

    foreach ($plan['pagesToCreate'] as &$page) {
        $impact = cms_seo_normalize_text((string) ($page['priority'] ?? 'medium'));
        $page['priority'] = $impactMap[$impact] ?? 'medium';
    }
    unset($page);

    foreach ($plan['duplicateRisks'] as &$risk) {
        $impact = cms_seo_normalize_text((string) ($risk['risk'] ?? 'medium'));
        $risk['risk'] = $impactMap[$impact] ?? 'medium';
    }
    unset($risk);

    return $plan;
}

function cms_seo_basic_action_plan(array $pages, array $keywords, array $suggestions, array $duplicateGroups): array
{
    $pagesToImprove = $pages;
    usort($pagesToImprove, static fn (array $left, array $right): int => ((int) ($left['seo_score'] ?? 0)) <=> ((int) ($right['seo_score'] ?? 0)));
    $pagesToImprove = array_slice(array_values(array_filter($pagesToImprove, static fn (array $page): bool => (int) ($page['seo_score'] ?? 0) < 75 || (string) ($page['seo_status'] ?? '') === 'improve')), 0, 6);

    $keywordOpportunities = [];
    foreach ($keywords as $keyword) {
        $seo = $keyword['_seo'] ?? cms_seo_match_keyword_to_page($keyword, $pages);
        if (in_array((string) ($seo['action']['key'] ?? ''), ['reinforce', 'create', 'associate'], true)) {
            $keywordOpportunities[] = [
                'keyword' => (string) ($keyword['keyword'] ?? ''),
                'recommendedTargetPage' => (string) ($seo['target_url'] ?? ''),
                'action' => (string) ($seo['action']['label'] ?? 'À décider'),
                'reason' => (string) ($seo['action']['detail'] ?? ''),
            ];
        }
    }

    return [
        'summary' => 'Plan calculé automatiquement à partir des scores pages, des mots-clés Search Console et des risques de doublons.',
        'weeklyPriorities' => array_values(array_filter([
            $pagesToImprove !== [] ? ['title' => 'Corriger les pages faibles', 'reason' => count($pagesToImprove) . ' page' . (count($pagesToImprove) > 1 ? 's' : '') . ' à renforcer en priorité.', 'impact' => 'high', 'actionLabel' => 'Voir les pages à améliorer', 'actionType' => 'improve_pages'] : null,
            $suggestions !== [] ? ['title' => 'Créer les pages manquantes utiles', 'reason' => min(5, count($suggestions)) . ' suggestion' . (count($suggestions) > 1 ? 's' : '') . ' sans page existante proche.', 'impact' => 'medium', 'actionLabel' => 'Voir les pages à créer', 'actionType' => 'create_pages'] : null,
            $keywordOpportunities !== [] ? ['title' => 'Exploiter les mots-clés Search Console', 'reason' => count($keywordOpportunities) . ' mot' . (count($keywordOpportunities) > 1 ? 's' : '') . '-clé' . (count($keywordOpportunities) > 1 ? 's' : '') . ' peuvent guider les contenus.', 'impact' => 'high', 'actionLabel' => 'Analyser les mots-clés', 'actionType' => 'keyword_opportunities'] : null,
            $duplicateGroups !== [] ? ['title' => 'Éviter les doublons SEO', 'reason' => count($duplicateGroups) . ' groupe' . (count($duplicateGroups) > 1 ? 's' : '') . ' à clarifier avec une page principale.', 'impact' => 'medium', 'actionLabel' => 'Voir les risques', 'actionType' => 'duplicate_risks'] : null,
        ])),
        'pagesToImprove' => array_map(static function (array $page): array {
            $score = cms_seo_page_score($page);
            $missing = array_values(array_map(static fn (array $check): string => (string) $check['label'], array_filter($score['checks'], static fn (array $check): bool => empty($check['ok']))));
            return ['pageId' => (string) ($page['id'] ?? ''), 'title' => (string) ($page['title'] ?? ''), 'reason' => $missing[0] ?? 'Page sous le seuil SEO recommandé.', 'recommendedActions' => array_slice($missing, 0, 5), 'priority' => (int) ($score['score'] ?? 0) < 55 ? 'high' : 'medium'];
        }, $pagesToImprove),
        'pagesToCreate' => array_map(static fn (array $item): array => ['title' => (string) ($item['title'] ?? ''), 'slug' => (string) ($item['slug'] ?? ''), 'reason' => 'Suggestion locale utile si aucune page principale ne couvre déjà cette intention.', 'template' => (string) ($item['template'] ?? ''), 'priority' => (string) ($item['priority'] ?? 'Normale')], array_slice($suggestions, 0, 5)),
        'keywordOpportunities' => array_slice($keywordOpportunities, 0, 8),
        'duplicateRisks' => array_map(static function (array $group): array {
            return ['commune' => (string) ($group[0]['city'] ?? 'Secteur'), 'intention' => cms_seo_intent_label((string) ($group[0]['seo_intent'] ?? 'autre')), 'risk' => 'medium', 'reason' => count($group) . ' pages ciblent la même commune et intention.', 'recommendation' => 'Conserver une seule page principale et intégrer les variantes dans cette page.'];
        }, array_slice($duplicateGroups, 0, 5)),
    ];
}

function cms_seo_seed_default_keywords(): int
{
    cms_ensure_seo_tables();

    $intents = [
        'estimation' => 'estimation immobilière %s',
        'vente' => 'vendre maison %s',
        'immobilier' => 'immobilier %s',
        'agence' => 'agence immobilière %s',
    ];
    $highPriorityCities = ['Arnay-le-Duc', 'Pouilly-en-Auxois', 'Autun', 'Dijon', 'Beaune', 'Saulieu', 'Nolay', 'Bligny-sur-Ouche', 'Liernais', 'Épinac'];
    $statement = cms_db()->prepare(
        'INSERT IGNORE INTO cms_seo_keywords (keyword, city, intent, target_url, priority, status, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $inserted = 0;

    foreach (cms_seo_local_cities() as $city) {
        foreach ($intents as $intent => $pattern) {
            foreach (cms_seo_city_keyword_variants($city) as $cityVariant) {
                $keyword = sprintf($pattern, $cityVariant);
                $priority = in_array($city, $highPriorityCities, true) ? 1 : 2;
                $status = $intent === 'estimation' ? 'active' : 'to-create';
                $statement->execute([
                    $keyword,
                    $city,
                    $intent,
                    cms_seo_target_url_for_city($city, $intent),
                    $priority,
                    $status,
                    'Mot clé local initial généré pour le suivi SEO.',
                ]);
                $inserted += $statement->rowCount();
            }
        }
    }

    return $inserted;
}

function cms_seo_keywords(array $filters = []): array
{
    cms_ensure_seo_tables();

    $where = [];
    $params = [];
    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(k.keyword LIKE ? OR k.city LIKE ? OR k.intent LIKE ? OR k.target_url LIKE ?)';
        $needle = '%' . $search . '%';
        array_push($params, $needle, $needle, $needle, $needle);
    }
    $city = trim((string) ($filters['city'] ?? ''));
    if ($city !== '') {
        $where[] = 'k.city = ?';
        $params[] = $city;
    }
    $status = trim((string) ($filters['status'] ?? ''));
    if ($status !== '' && isset(cms_seo_keyword_statuses()[$status])) {
        $where[] = 'k.status = ?';
        $params[] = $status;
    }

    $sql = "SELECT k.*, m.period_start, m.period_end, m.clicks, m.impressions, m.ctr, m.position, m.best_page, m.source AS measurement_source
              FROM cms_seo_keywords k
              LEFT JOIN cms_seo_measurements m
                ON m.id = (
                  SELECT mm.id
                    FROM cms_seo_measurements mm
                   WHERE mm.keyword_id = k.id
                   ORDER BY mm.period_end DESC, mm.created_at DESC, mm.id DESC
                   LIMIT 1
                )";
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY k.priority ASC, k.city ASC, k.intent ASC, k.keyword ASC LIMIT 300';

    $statement = cms_db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function cms_seo_keyword(int $id): ?array
{
    cms_ensure_seo_tables();
    $statement = cms_db()->prepare('SELECT * FROM cms_seo_keywords WHERE id = ? LIMIT 1');
    $statement->execute([$id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function cms_save_seo_keyword(array $payload, ?int $id = null): int
{
    cms_ensure_seo_tables();
    $current = $id !== null ? (cms_seo_keyword($id) ?? []) : [];

    $keyword = trim((string) ($payload['keyword'] ?? ''));
    if ($keyword === '') {
        throw new RuntimeException('Le mot clé est obligatoire.');
    }

    $status = (string) ($payload['status'] ?? 'active');
    if (!isset(cms_seo_keyword_statuses()[$status])) {
        $status = 'active';
    }
    $actionStatus = trim((string) ($payload['action_status'] ?? ($current['action_status'] ?? '')));
    if ($actionStatus !== '' && !isset(cms_seo_keyword_action_statuses()[$actionStatus])) {
        $actionStatus = '';
    }
    $targetPageIdValue = $payload['target_page_id'] ?? ($current['target_page_id'] ?? null);
    $targetPageId = isset($targetPageIdValue) && (int) $targetPageIdValue > 0 ? (int) $targetPageIdValue : null;
    $priority = max(1, min(3, (int) ($payload['priority'] ?? 2)));

    if ($id === null) {
        $statement = cms_db()->prepare(
            'INSERT INTO cms_seo_keywords (keyword, city, intent, target_url, target_page_id, priority, status, action_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $keyword,
            trim((string) ($payload['city'] ?? '')) ?: null,
            trim((string) ($payload['intent'] ?? 'local')) ?: 'local',
            trim((string) ($payload['target_url'] ?? '')) ?: null,
            $targetPageId,
            $priority,
            $status,
            $actionStatus ?: null,
            trim((string) ($payload['notes'] ?? '')) ?: null,
        ]);
        return (int) cms_db()->lastInsertId();
    }

    $statement = cms_db()->prepare(
        'UPDATE cms_seo_keywords SET keyword = ?, city = ?, intent = ?, target_url = ?, target_page_id = ?, priority = ?, status = ?, action_status = ?, notes = ? WHERE id = ?'
    );
    $statement->execute([
        $keyword,
        trim((string) ($payload['city'] ?? '')) ?: null,
        trim((string) ($payload['intent'] ?? 'local')) ?: 'local',
        trim((string) ($payload['target_url'] ?? '')) ?: null,
        $targetPageId,
        $priority,
        $status,
        $actionStatus ?: null,
        trim((string) ($payload['notes'] ?? '')) ?: null,
        $id,
    ]);

    return $id;
}

function cms_delete_seo_keyword(int $id): void
{
    cms_ensure_seo_tables();
    $statement = cms_db()->prepare('DELETE FROM cms_seo_keywords WHERE id = ? LIMIT 1');
    $statement->execute([$id]);
}

function cms_save_seo_measurement(int $keywordId, array $payload): void
{
    cms_ensure_seo_tables();
    $clicks = max(0, (int) ($payload['clicks'] ?? 0));
    $impressions = max(0, (int) ($payload['impressions'] ?? 0));
    $ctr = isset($payload['ctr']) ? (float) $payload['ctr'] : ($impressions > 0 ? $clicks / $impressions : 0.0);
    if ($ctr > 1) {
        $ctr = $ctr / 100;
    }

    $statement = cms_db()->prepare(
        'INSERT INTO cms_seo_measurements (keyword_id, source, period_start, period_end, clicks, impressions, ctr, position, best_page, raw_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $keywordId,
        in_array(($payload['source'] ?? 'manual'), ['manual', 'csv', 'search_console'], true) ? $payload['source'] : 'manual',
        (string) ($payload['period_start'] ?? date('Y-m-d', strtotime('-28 days'))),
        (string) ($payload['period_end'] ?? date('Y-m-d')),
        $clicks,
        $impressions,
        $ctr,
        isset($payload['position']) && $payload['position'] !== '' ? (float) $payload['position'] : null,
        trim((string) ($payload['best_page'] ?? '')) ?: null,
        isset($payload['raw']) ? json_encode($payload['raw'], JSON_UNESCAPED_UNICODE) : null,
    ]);
}

function cms_seo_summary(): array
{
    cms_ensure_seo_tables();
    $keywordCount = (int) cms_db()->query('SELECT COUNT(*) FROM cms_seo_keywords')->fetchColumn();
    $latestRows = cms_db()->query(
        'SELECT m.*
           FROM cms_seo_measurements m
           INNER JOIN (
             SELECT keyword_id, MAX(id) AS max_id
               FROM cms_seo_measurements
              GROUP BY keyword_id
           ) latest ON latest.max_id = m.id'
    )->fetchAll();

    $clicks = 0;
    $impressions = 0;
    $positionWeighted = 0.0;
    $positionWeight = 0;
    foreach ($latestRows as $row) {
        $rowClicks = (int) ($row['clicks'] ?? 0);
        $rowImpressions = (int) ($row['impressions'] ?? 0);
        $clicks += $rowClicks;
        $impressions += $rowImpressions;
        if ($rowImpressions > 0 && $row['position'] !== null) {
            $positionWeighted += (float) $row['position'] * $rowImpressions;
            $positionWeight += $rowImpressions;
        }
    }

    return [
        'keywords' => $keywordCount,
        'clicks' => $clicks,
        'impressions' => $impressions,
        'ctr' => $impressions > 0 ? $clicks / $impressions : 0,
        'position' => $positionWeight > 0 ? $positionWeighted / $positionWeight : null,
    ];
}

function cms_normalize_csv_header(string $header): string
{
    $header = mb_strtolower(trim($header));
    $header = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) ?: $header;
    return preg_replace('/[^a-z0-9]+/', '_', $header) ?: $header;
}

function cms_parse_number_string(string $value): float
{
    $value = trim(str_replace(["\xc2\xa0", ' ', '%'], '', $value));
    $value = str_replace(',', '.', $value);
    return is_numeric($value) ? (float) $value : 0.0;
}

function cms_import_search_console_csv(string $csv, string $periodStart, string $periodEnd): int
{
    cms_ensure_seo_tables();
    $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];
    if (count($lines) < 2) {
        return 0;
    }

    $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
    $headers = array_map('cms_normalize_csv_header', str_getcsv($lines[0], $delimiter));
    $map = array_flip($headers);
    $queryIndex = $map['requete'] ?? $map['requetes'] ?? $map['query'] ?? $map['queries'] ?? null;
    $clicksIndex = $map['clics'] ?? $map['clicks'] ?? null;
    $impressionsIndex = $map['impressions'] ?? null;
    $ctrIndex = $map['ctr'] ?? null;
    $positionIndex = $map['position'] ?? null;

    if ($queryIndex === null) {
        throw new RuntimeException('Colonne requête/query introuvable dans le CSV.');
    }

    $keywords = [];
    foreach (cms_seo_keywords() as $keyword) {
        $keywords[mb_strtolower((string) $keyword['keyword'])] = (int) $keyword['id'];
    }

    $imported = 0;
    for ($i = 1; $i < count($lines); $i++) {
        if (trim($lines[$i]) === '') {
            continue;
        }
        $row = str_getcsv($lines[$i], $delimiter);
        $query = mb_strtolower(trim((string) ($row[$queryIndex] ?? '')));
        if ($query === '' || !isset($keywords[$query])) {
            continue;
        }
        cms_save_seo_measurement($keywords[$query], [
            'source' => 'csv',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'clicks' => $clicksIndex !== null ? (int) cms_parse_number_string((string) ($row[$clicksIndex] ?? '0')) : 0,
            'impressions' => $impressionsIndex !== null ? (int) cms_parse_number_string((string) ($row[$impressionsIndex] ?? '0')) : 0,
            'ctr' => $ctrIndex !== null ? cms_parse_number_string((string) ($row[$ctrIndex] ?? '0')) : 0,
            'position' => $positionIndex !== null ? cms_parse_number_string((string) ($row[$positionIndex] ?? '')) : null,
            'raw' => $row,
        ]);
        $imported++;
    }

    return $imported;
}

function cms_base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function cms_http_post(string $url, array $headers, string $body, int $timeout = 25): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Erreur HTTP externe : ' . $error);
        }

        return ['status' => $status, 'body' => (string) $response];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $response = file_get_contents($url, false, $context);
    $status = 0;
    foreach (($http_response_header ?? []) as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $matches) === 1) {
            $status = (int) $matches[1];
            break;
        }
    }

    return ['status' => $status, 'body' => (string) $response];
}

function cms_openai_configured(): bool
{
    return trim((string) (cms_config()['openai_api_key'] ?? '')) !== '';
}

function cms_openai_call(string $prompt, int $maxOutputTokens = 900, int $timeout = 55, ?array $textFormat = null): string
{
    $config = cms_config();
    $apiKey = trim((string) ($config['openai_api_key'] ?? ''));
    if ($apiKey === '') {
        throw new RuntimeException('Clé OpenAI absente. Ajoutez OPENAI_API_KEY dans le .env OVH.');
    }

    $requestPayload = [
        'model' => trim((string) ($config['openai_model'] ?? 'gpt-5.5')) ?: 'gpt-5.5',
        'input' => [
            ['role' => 'system', 'content' => 'Tu es un consultant SEO local spécialisé dans les sites immobiliers. Tu aides un conseiller immobilier indépendant à améliorer son référencement naturel local autour de Mimeure, l’Auxois, la Côte-d’Or, Autun, Beaune, Dijon, Arnay-le-Duc, Pouilly-en-Auxois, Vitteaux, Saulieu et Semur-en-Auxois. Ton rôle est de proposer des actions concrètes, pas des explications générales. Tu dois éviter les doublons SEO et recommander de renforcer une page existante quand une nouvelle page serait trop proche. Ne propose jamais de publier automatiquement.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'max_output_tokens' => $maxOutputTokens,
    ];
    if ($textFormat !== null) {
        $requestPayload['text'] = ['format' => $textFormat];
    }
    $payload = json_encode($requestPayload, JSON_UNESCAPED_UNICODE);

    if (!is_string($payload)) {
        throw new RuntimeException('Impossible de préparer la requête IA.');
    }

    $response = cms_http_post('https://api.openai.com/v1/responses', [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ], $payload, $timeout);

    if ((int) ($response['status'] ?? 0) < 200 || (int) ($response['status'] ?? 0) >= 300) {
        $body = json_decode((string) ($response['body'] ?? ''), true);
        $message = is_array($body) ? (string) ($body['error']['message'] ?? 'Erreur OpenAI.') : 'Erreur OpenAI.';
        throw new RuntimeException($message);
    }

    $decoded = json_decode((string) ($response['body'] ?? ''), true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Réponse OpenAI illisible.');
    }
    if (isset($decoded['output_text']) && is_string($decoded['output_text']) && trim($decoded['output_text']) !== '') {
        return trim($decoded['output_text']);
    }

    $parts = [];
    foreach ((array) ($decoded['output'] ?? []) as $output) {
        foreach ((array) ($output['content'] ?? []) as $content) {
            if (isset($content['text']) && is_string($content['text'])) {
                $parts[] = $content['text'];
            }
        }
    }
    $text = trim(implode("\n", $parts));
    if ($text === '') {
        throw new RuntimeException('Réponse OpenAI vide.');
    }

    return $text;
}

function cms_openai_ping(): string
{
    return cms_openai_call('Test de connexion. Réponds uniquement : OK IA SEO connectée.', 40, 20);
}

function cms_seo_ai_page_prompt(array $page): string
{
    $score = cms_seo_page_score($page);
    $keywords = !empty($page['id']) ? cms_seo_keywords_for_page($page) : [];
    $keywordLines = [];
    foreach (array_slice($keywords, 0, 6) as $keyword) {
        $keywordLines[] = '- ' . (string) $keyword['keyword'] . ' | impressions: ' . (int) ($keyword['impressions'] ?? 0) . ' | clics: ' . (int) ($keyword['clicks'] ?? 0) . ' | position: ' . ($keyword['position'] !== null ? number_format((float) $keyword['position'], 1, '.', '') : 'n/a');
    }

    $sectionsText = strip_tags((string) ($page['intro_html'] ?? '') . ' ' . (string) ($page['sections_json'] ?? '') . ' ' . (string) ($page['cta_text'] ?? ''));
    $sectionsText = mb_substr(preg_replace('/\s+/', ' ', $sectionsText) ?: $sectionsText, 0, 2400);

    return "Analyse cette page SEO locale et réponds avec ces rubriques exactes :\n"
        . "1. Verdict : Renforcer / Créer une autre page / Fusionner / Ignorer\n"
        . "2. Pourquoi\n"
        . "3. 5 actions prioritaires\n"
        . "4. Mots-clés à intégrer naturellement\n"
        . "5. Meta description proposée\n"
        . "6. FAQ locale proposée\n"
        . "7. Risque de doublon SEO\n\n"
        . "Contexte de la page :\n"
        . "Titre: " . (string) ($page['title'] ?? '') . "\n"
        . "Slug: " . (string) ($page['slug'] ?? '') . "\n"
        . "Commune/secteur: " . (string) ($page['city'] ?? '') . "\n"
        . "Intention SEO: " . cms_seo_intent_label((string) ($page['seo_intent'] ?? 'autre')) . "\n"
        . "Mot-clé principal: " . (string) ($page['seo_focus_keyword'] ?? '') . "\n"
        . "Mots-clés secondaires: " . (string) ($page['seo_secondary_keywords'] ?? '') . "\n"
        . "Score interne: " . (int) $score['score'] . "/100, " . (string) $score['label'] . ", " . (int) $score['word_count'] . " mots\n"
        . "Mots-clés Search Console associés:\n" . ($keywordLines !== [] ? implode("\n", $keywordLines) : '- Aucun mot-clé associé pour le moment') . "\n\n"
        . "Contenu actuel résumé:\n" . $sectionsText;
}

function cms_generate_seo_ai_page_advice(array $page): string
{
    return cms_openai_call(cms_seo_ai_page_prompt($page), 850, 55);
}

function cms_seo_ai_overview_json_schema(): array
{
    $priority = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['title', 'reason', 'impact', 'actionLabel', 'actionType'],
        'properties' => [
            'title' => ['type' => 'string'],
            'reason' => ['type' => 'string'],
            'impact' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
            'actionLabel' => ['type' => 'string'],
            'actionType' => ['type' => 'string', 'enum' => ['improve_pages', 'create_pages', 'keyword_opportunities', 'duplicate_risks', 'run_ai']],
        ],
    ];
    $pageToImprove = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['pageId', 'title', 'reason', 'recommendedActions', 'priority'],
        'properties' => [
            'pageId' => ['type' => 'string'],
            'title' => ['type' => 'string'],
            'reason' => ['type' => 'string'],
            'recommendedActions' => ['type' => 'array', 'items' => ['type' => 'string']],
            'priority' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
        ],
    ];
    $pageToCreate = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['title', 'slug', 'reason', 'template', 'priority'],
        'properties' => [
            'title' => ['type' => 'string'],
            'slug' => ['type' => 'string'],
            'reason' => ['type' => 'string'],
            'template' => ['type' => 'string'],
            'priority' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
        ],
    ];
    $keywordOpportunity = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['keyword', 'recommendedTargetPage', 'action', 'reason'],
        'properties' => [
            'keyword' => ['type' => 'string'],
            'recommendedTargetPage' => ['type' => 'string'],
            'action' => ['type' => 'string', 'enum' => ['Renforcer une page existante', 'Créer une nouvelle page', 'Associer manuellement', 'Ignorer']],
            'reason' => ['type' => 'string'],
        ],
    ];
    $duplicateRisk = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['commune', 'intention', 'risk', 'reason', 'recommendation'],
        'properties' => [
            'commune' => ['type' => 'string'],
            'intention' => ['type' => 'string'],
            'risk' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
            'reason' => ['type' => 'string'],
            'recommendation' => ['type' => 'string'],
        ],
    ];

    return [
        'type' => 'json_schema',
        'name' => 'seo_action_plan',
        'strict' => true,
        'schema' => [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['summary', 'weeklyPriorities', 'pagesToImprove', 'pagesToCreate', 'keywordOpportunities', 'duplicateRisks'],
            'properties' => [
                'summary' => ['type' => 'string'],
                'weeklyPriorities' => ['type' => 'array', 'items' => $priority],
                'pagesToImprove' => ['type' => 'array', 'items' => $pageToImprove],
                'pagesToCreate' => ['type' => 'array', 'items' => $pageToCreate],
                'keywordOpportunities' => ['type' => 'array', 'items' => $keywordOpportunity],
                'duplicateRisks' => ['type' => 'array', 'items' => $duplicateRisk],
            ],
        ],
    ];
}

function cms_seo_ai_overview_prompt(array $pages, array $keywords, array $suggestions, array $duplicateGroups): string
{
    $pageRows = [];
    foreach (array_slice($pages, 0, 40) as $page) {
        $score = cms_seo_page_score($page);
        $pageRows[] = [
            'id' => (int) ($page['id'] ?? 0),
            'title' => (string) ($page['title'] ?? ''),
            'slug' => (string) ($page['slug'] ?? ''),
            'city' => (string) ($page['city'] ?? ''),
            'intent' => cms_seo_intent_label((string) ($page['seo_intent'] ?? 'autre')),
            'status' => (string) ($page['seo_status'] ?? ''),
            'score' => (int) ($score['score'] ?? 0),
            'wordCount' => (int) ($score['word_count'] ?? 0),
            'missing' => array_values(array_map(static fn (array $check): string => (string) $check['label'], array_filter($score['checks'], static fn (array $check): bool => empty($check['ok'])))),
            'metaDescriptionLength' => mb_strlen((string) ($page['meta_description'] ?? '')),
            'h1' => (string) ($page['h1'] ?? ''),
            'isPrimary' => !empty($page['seo_is_primary']),
        ];
    }

    $keywordRows = [];
    foreach (array_slice($keywords, 0, 45) as $keyword) {
        $seo = $keyword['_seo'] ?? [];
        $keywordRows[] = [
            'keyword' => (string) ($keyword['keyword'] ?? ''),
            'city' => (string) ($seo['city'] ?? $keyword['city'] ?? ''),
            'intent' => (string) ($seo['intent_label'] ?? $keyword['intent'] ?? ''),
            'clicks' => (int) ($keyword['clicks'] ?? 0),
            'impressions' => (int) ($keyword['impressions'] ?? 0),
            'ctr' => isset($keyword['ctr']) ? (float) $keyword['ctr'] : 0,
            'position' => $keyword['position'] !== null ? (float) $keyword['position'] : null,
            'recommendedTargetPage' => (string) ($seo['target_url'] ?? ''),
            'recommendedAction' => (string) ($seo['action']['label'] ?? ''),
            'reason' => (string) ($seo['action']['detail'] ?? ''),
        ];
    }

    $payload = [
        'context' => [
            'mainArea' => 'Mimeure, Auxois, Côte-d’Or, Autun, Beaune, Dijon, Arnay-le-Duc, Pouilly-en-Auxois, Vitteaux, Saulieu, Semur-en-Auxois',
            'goal' => 'Prioriser les actions SEO locales utiles pour capter vendeurs, estimations, viager et projets immobiliers locaux.',
        ],
        'pages' => $pageRows,
        'keywords' => $keywordRows,
        'suggestions' => array_slice($suggestions, 0, 20),
        'duplicateGroups' => array_map(static fn (array $group): array => ['city' => (string) ($group[0]['city'] ?? ''), 'intent' => cms_seo_intent_label((string) ($group[0]['seo_intent'] ?? 'autre')), 'slugs' => array_map(static fn (array $page): string => (string) ($page['slug'] ?? ''), $group)], array_slice($duplicateGroups, 0, 10)),
    ];

    return "Analyse ces pages SEO, ces mots-clés Search Console et ces suggestions. Retourne uniquement un JSON strict, sans markdown, en français, selon ce schéma exact :\n"
        . '{"summary":"...","weeklyPriorities":[{"title":"...","reason":"...","impact":"high|medium|low","actionLabel":"...","actionType":"improve_pages|create_pages|keyword_opportunities|duplicate_risks|run_ai"}],"pagesToImprove":[{"pageId":"...","title":"...","reason":"...","recommendedActions":["..."],"priority":"high|medium|low"}],"pagesToCreate":[{"title":"...","slug":"...","reason":"...","template":"...","priority":"high|medium|low"}],"keywordOpportunities":[{"keyword":"...","recommendedTargetPage":"...","action":"Renforcer une page existante|Créer une nouvelle page|Associer manuellement|Ignorer","reason":"..."}],"duplicateRisks":[{"commune":"...","intention":"...","risk":"high|medium|low","reason":"...","recommendation":"..."}]}'."\n"
        . "Règles : propose des actions concrètes, évite les doublons SEO, recommande de renforcer une page existante quand elle peut répondre à la même commune et intention, limite chaque liste à 6 éléments. Ne propose pas de créer une page si une page existante peut répondre à la même intention.\n\n"
        . json_encode($payload, JSON_UNESCAPED_UNICODE);
}

function cms_generate_seo_ai_overview_analysis(array $pages, array $keywords, array $suggestions, array $duplicateGroups): array
{
    $prompt = cms_seo_ai_overview_prompt($pages, $keywords, $suggestions, $duplicateGroups);
    $text = cms_openai_call($prompt, 2600, 75, cms_seo_ai_overview_json_schema());
    try {
        $result = cms_seo_ai_extract_json($text);
    } catch (Throwable $exception) {
        $fallback = cms_seo_basic_action_plan($pages, $keywords, $suggestions, $duplicateGroups);
        $fallback['summary'] = 'L’IA a répondu, mais son format était incomplet. Un plan automatique fiable est affiché en attendant de relancer l’analyse.';
        $fallback['_debug'] = mb_substr(preg_replace('/\s+/', ' ', $text) ?: $text, 0, 900);
        return $fallback;
    }
    $result = cms_seo_normalize_ai_plan($result);
    foreach (['summary', 'weeklyPriorities', 'pagesToImprove', 'pagesToCreate', 'keywordOpportunities', 'duplicateRisks'] as $key) {
        if (!array_key_exists($key, $result)) {
            $result[$key] = in_array($key, ['summary'], true) ? '' : [];
        }
    }

    return $result;
}

function cms_search_console_oauth_config(): array
{
    $config = cms_config();
    $redirectUri = trim((string) ($config['google_redirect_uri'] ?? ''));
    if ($redirectUri === '') {
        $redirectUri = cms_absolute_url('/api/admin/search-console/auth/callback');
    }

    return [
        'client_id' => trim((string) ($config['google_client_id'] ?? '')),
        'client_secret' => trim((string) ($config['google_client_secret'] ?? '')),
        'redirect_uri' => $redirectUri,
        'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
    ];
}

function cms_search_console_oauth_configured(): bool
{
    $config = cms_search_console_oauth_config();
    return $config['client_id'] !== '' && $config['client_secret'] !== '' && $config['redirect_uri'] !== '';
}

function cms_search_console_oauth_authorize_url(string $state): string
{
    $config = cms_search_console_oauth_config();
    if (!cms_search_console_oauth_configured()) {
        throw new RuntimeException('Configuration OAuth Google incomplète. Ajoutez GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET et GOOGLE_REDIRECT_URI dans .env.');
    }

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => $config['scope'],
        'access_type' => 'offline',
        'prompt' => 'consent',
        'include_granted_scopes' => 'true',
        'state' => $state,
    ]);
}

function cms_search_console_oauth_token(): ?array
{
    cms_ensure_seo_tables();
    $statement = cms_db()->prepare('SELECT * FROM cms_seo_oauth_tokens WHERE provider = ? LIMIT 1');
    $statement->execute(['google_search_console']);
    $row = $statement->fetch();
    return $row ?: null;
}

function cms_save_search_console_oauth_token(array $payload): void
{
    cms_ensure_seo_tables();
    $existing = cms_search_console_oauth_token();
    $refreshToken = trim((string) ($payload['refresh_token'] ?? ''));
    if ($refreshToken === '' && $existing && !empty($existing['refresh_token'])) {
        $refreshToken = (string) $existing['refresh_token'];
    }
    $expiresIn = max(0, (int) ($payload['expires_in'] ?? 0));
    $expiresAt = $expiresIn > 0 ? date('Y-m-d H:i:s', time() + $expiresIn - 60) : null;

    $statement = cms_db()->prepare(
        'INSERT INTO cms_seo_oauth_tokens (provider, access_token, refresh_token, token_type, scope, expires_at)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), refresh_token = VALUES(refresh_token), token_type = VALUES(token_type), scope = VALUES(scope), expires_at = VALUES(expires_at)'
    );
    $statement->execute([
        'google_search_console',
        trim((string) ($payload['access_token'] ?? '')),
        $refreshToken ?: null,
        trim((string) ($payload['token_type'] ?? 'Bearer')) ?: 'Bearer',
        trim((string) ($payload['scope'] ?? '')) ?: null,
        $expiresAt,
    ]);
}

function cms_delete_search_console_oauth_token(): void
{
    cms_ensure_seo_tables();
    $statement = cms_db()->prepare('DELETE FROM cms_seo_oauth_tokens WHERE provider = ? LIMIT 1');
    $statement->execute(['google_search_console']);
}

function cms_exchange_search_console_oauth_code(string $code): void
{
    if (!cms_search_console_oauth_configured()) {
        throw new RuntimeException('Configuration OAuth Google incomplète.');
    }

    $config = cms_search_console_oauth_config();
    $response = cms_http_post('https://oauth2.googleapis.com/token', ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $config['redirect_uri'],
    ]));
    $data = json_decode($response['body'], true);
    if ($response['status'] >= 400 || !is_array($data) || empty($data['access_token'])) {
        throw new RuntimeException('Google a refusé la connexion Search Console : ' . mb_substr($response['body'], 0, 300));
    }

    cms_save_search_console_oauth_token($data);
}

function cms_refresh_search_console_oauth_access_token(array $token): string
{
    if (!cms_search_console_oauth_configured()) {
        throw new RuntimeException('Configuration OAuth Google incomplète.');
    }
    $refreshToken = trim((string) ($token['refresh_token'] ?? ''));
    if ($refreshToken === '') {
        throw new RuntimeException('Jeton Search Console incomplet : reconnectez Google depuis l’admin SEO.');
    }

    $config = cms_search_console_oauth_config();
    $response = cms_http_post('https://oauth2.googleapis.com/token', ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token',
    ]));
    $data = json_decode($response['body'], true);
    if ($response['status'] >= 400 || !is_array($data) || empty($data['access_token'])) {
        throw new RuntimeException('Impossible de rafraîchir le jeton Search Console : ' . mb_substr($response['body'], 0, 300));
    }
    $data['refresh_token'] = $refreshToken;
    cms_save_search_console_oauth_token($data);

    return (string) $data['access_token'];
}

function cms_search_console_oauth_access_token(): ?string
{
    if (!cms_search_console_oauth_configured()) {
        return null;
    }
    $token = cms_search_console_oauth_token();
    if (!$token) {
        return null;
    }

    $accessToken = trim((string) ($token['access_token'] ?? ''));
    $expiresAt = strtotime((string) ($token['expires_at'] ?? '')) ?: 0;
    if ($accessToken !== '' && $expiresAt > time() + 60) {
        return $accessToken;
    }

    return cms_refresh_search_console_oauth_access_token($token);
}

function cms_search_console_connection_status(): array
{
    $oauthConfig = cms_search_console_oauth_config();
    $oauthToken = cms_search_console_oauth_token();
    $serviceCredentials = cms_search_console_credentials();
    $oauthConfigured = cms_search_console_oauth_configured();
    $oauthConnected = $oauthConfigured && is_array($oauthToken) && (!empty($oauthToken['refresh_token']) || !empty($oauthToken['access_token']));
    $serviceReady = is_array($serviceCredentials) && !empty($serviceCredentials['client_email']);

    return [
        'ready' => $oauthConnected || $serviceReady,
        'method' => $oauthConnected ? 'OAuth Google' : ($serviceReady ? 'Compte de service' : 'Non connecté'),
        'oauth_configured' => $oauthConfigured,
        'oauth_connected' => $oauthConnected,
        'oauth_redirect_uri' => (string) $oauthConfig['redirect_uri'],
        'service_email' => $serviceReady ? (string) $serviceCredentials['client_email'] : '',
        'token_expires_at' => is_array($oauthToken) ? (string) ($oauthToken['expires_at'] ?? '') : '',
    ];
}

function cms_search_console_credentials(): ?array
{
    $config = cms_config();
    $raw = '';
    $encoded = trim((string) ($config['search_console_credentials_base64'] ?? ''));
    $path = trim((string) ($config['search_console_credentials_path'] ?? ''));

    if ($encoded !== '') {
        $decoded = base64_decode($encoded, true);
        $raw = is_string($decoded) ? $decoded : '';
    } elseif ($path !== '' && is_file($path)) {
        $raw = (string) file_get_contents($path);
    }

    if ($raw === '') {
        return null;
    }

    $credentials = json_decode($raw, true);
    return is_array($credentials) ? $credentials : null;
}

function cms_search_console_access_token(): string
{
    $oauthAccessToken = cms_search_console_oauth_access_token();
    if (is_string($oauthAccessToken) && $oauthAccessToken !== '') {
        return $oauthAccessToken;
    }

    $credentials = cms_search_console_credentials();
    if (!$credentials || empty($credentials['client_email']) || empty($credentials['private_key'])) {
        throw new RuntimeException('Identifiants Search Console manquants. Connectez Google depuis l’admin SEO ou ajoutez SEARCH_CONSOLE_CREDENTIALS_PATH / SEARCH_CONSOLE_CREDENTIALS_BASE64 dans .env.');
    }

    $tokenUri = (string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token');
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss' => (string) $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
        'aud' => $tokenUri,
        'iat' => $now,
        'exp' => $now + 3600,
    ];
    $headerJson = json_encode($header, JSON_UNESCAPED_SLASHES);
    $claimsJson = json_encode($claims, JSON_UNESCAPED_SLASHES);
    if (!is_string($headerJson) || !is_string($claimsJson)) {
        throw new RuntimeException('Impossible de préparer le jeton Search Console.');
    }
    $unsigned = cms_base64url_encode($headerJson) . '.' . cms_base64url_encode($claimsJson);
    $signature = '';
    if (!openssl_sign($unsigned, $signature, (string) $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Impossible de signer la requête Search Console.');
    }
    $assertion = $unsigned . '.' . cms_base64url_encode($signature);
    $response = cms_http_post($tokenUri, ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $assertion,
    ]));
    $data = json_decode($response['body'], true);
    if ($response['status'] >= 400 || !is_array($data) || empty($data['access_token'])) {
        throw new RuntimeException('Google a refusé l’authentification Search Console : ' . mb_substr($response['body'], 0, 300));
    }

    return (string) $data['access_token'];
}

function cms_search_console_query_keyword(string $accessToken, string $keyword, string $startDate, string $endDate): array
{
    $siteUrl = (string) (cms_config()['search_console_site_url'] ?? '');
    if ($siteUrl === '') {
        throw new RuntimeException('SEARCH_CONSOLE_SITE_URL est manquant.');
    }

    $endpoint = 'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/searchAnalytics/query';
    $payload = [
        'startDate' => $startDate,
        'endDate' => $endDate,
        'dimensions' => ['query', 'page'],
        'rowLimit' => 25,
        'dimensionFilterGroups' => [[
            'filters' => [[
                'dimension' => 'query',
                'operator' => 'equals',
                'expression' => $keyword,
            ]],
        ]],
    ];
    $response = cms_http_post($endpoint, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ], json_encode($payload, JSON_UNESCAPED_UNICODE));
    $data = json_decode($response['body'], true);
    if ($response['status'] >= 400 || !is_array($data)) {
        throw new RuntimeException('Erreur Search Console : ' . mb_substr($response['body'], 0, 300));
    }

    $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
    $clicks = 0;
    $impressions = 0;
    $positionWeighted = 0.0;
    $bestPage = '';
    $bestPageClicks = -1;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rowClicks = (int) ($row['clicks'] ?? 0);
        $rowImpressions = (int) ($row['impressions'] ?? 0);
        $clicks += $rowClicks;
        $impressions += $rowImpressions;
        if ($rowImpressions > 0) {
            $positionWeighted += (float) ($row['position'] ?? 0) * $rowImpressions;
        }
        $keys = is_array($row['keys'] ?? null) ? $row['keys'] : [];
        $page = (string) ($keys[1] ?? '');
        if ($page !== '' && $rowClicks > $bestPageClicks) {
            $bestPageClicks = $rowClicks;
            $bestPage = $page;
        }
    }

    return [
        'clicks' => $clicks,
        'impressions' => $impressions,
        'ctr' => $impressions > 0 ? $clicks / $impressions : 0,
        'position' => $impressions > 0 ? $positionWeighted / $impressions : null,
        'best_page' => $bestPage,
        'raw' => $data,
    ];
}

function cms_search_console_query_all(string $accessToken, string $startDate, string $endDate): array
{
    $siteUrl = (string) (cms_config()['search_console_site_url'] ?? '');
    if ($siteUrl === '') {
        throw new RuntimeException('SEARCH_CONSOLE_SITE_URL est manquant.');
    }

    $endpoint = 'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/searchAnalytics/query';
    $payload = [
        'startDate' => $startDate,
        'endDate' => $endDate,
        'dimensions' => ['query', 'page'],
        'rowLimit' => 25000,
    ];
    $response = cms_http_post($endpoint, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ], json_encode($payload, JSON_UNESCAPED_UNICODE));
    $data = json_decode($response['body'], true);
    if ($response['status'] >= 400 || !is_array($data)) {
        throw new RuntimeException('Erreur Search Console : ' . mb_substr($response['body'], 0, 300));
    }

    return is_array($data['rows'] ?? null) ? $data['rows'] : [];
}

function cms_sync_search_console_keywords(string $periodKey = '28d'): array
{
    cms_ensure_seo_tables();
    $days = match ($periodKey) {
        '7d' => 7,
        '90d' => 90,
        default => 28,
    };
    $endDate = date('Y-m-d', strtotime('-2 days'));
    $startDate = date('Y-m-d', strtotime('-' . ($days + 1) . ' days'));
    $accessToken = cms_search_console_access_token();
    $keywords = array_values(array_filter(cms_seo_keywords(), static fn (array $keyword): bool => (string) ($keyword['status'] ?? '') !== 'paused'));
    $keywordMap = [];
    foreach ($keywords as $keyword) {
        $keywordMap[mb_strtolower(trim((string) $keyword['keyword']))] = [
            'id' => (int) $keyword['id'],
            'keyword' => (string) $keyword['keyword'],
            'clicks' => 0,
            'impressions' => 0,
            'position_weighted' => 0.0,
            'best_page' => '',
            'best_page_clicks' => -1,
            'rows' => [],
        ];
    }

    $rows = cms_search_console_query_all($accessToken, $startDate, $endDate);
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $keys = is_array($row['keys'] ?? null) ? $row['keys'] : [];
        $query = mb_strtolower(trim((string) ($keys[0] ?? '')));
        if ($query === '' || !isset($keywordMap[$query])) {
            continue;
        }

        $rowClicks = (int) ($row['clicks'] ?? 0);
        $rowImpressions = (int) ($row['impressions'] ?? 0);
        $keywordMap[$query]['clicks'] += $rowClicks;
        $keywordMap[$query]['impressions'] += $rowImpressions;
        $keywordMap[$query]['position_weighted'] += (float) ($row['position'] ?? 0) * $rowImpressions;
        $keywordMap[$query]['rows'][] = $row;
        $page = (string) ($keys[1] ?? '');
        if ($page !== '' && $rowClicks > $keywordMap[$query]['best_page_clicks']) {
            $keywordMap[$query]['best_page_clicks'] = $rowClicks;
            $keywordMap[$query]['best_page'] = $page;
        }
    }

    $synced = 0;
    $errors = [];

    foreach ($keywordMap as $entry) {
        try {
            $impressions = (int) $entry['impressions'];
            cms_save_seo_measurement((int) $entry['id'], [
                'source' => 'search_console',
                'period_start' => $startDate,
                'period_end' => $endDate,
                'clicks' => (int) $entry['clicks'],
                'impressions' => $impressions,
                'ctr' => $impressions > 0 ? ((int) $entry['clicks'] / $impressions) : 0,
                'position' => $impressions > 0 ? ((float) $entry['position_weighted'] / $impressions) : null,
                'best_page' => (string) $entry['best_page'],
                'raw' => $entry['rows'],
            ]);
            $synced++;
        } catch (Throwable $exception) {
            $errors[] = (string) $entry['keyword'] . ' : ' . $exception->getMessage();
            if (count($errors) >= 5) {
                break;
            }
        }
    }

    return ['synced' => $synced, 'errors' => $errors, 'start' => $startDate, 'end' => $endDate];
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
    $legacyNormalized = $normalized === '/' ? '/' : ltrim($normalized, '/');
    $statement = cms_db()->prepare(
        "SELECT *
           FROM cms_pages
          WHERE status = 'published'
            AND (slug = ? OR slug = ?)
          ORDER BY CASE WHEN slug = ? THEN 0 ELSE 1 END
          LIMIT 1"
    );
    $statement->execute([$normalized, $legacyNormalized, $normalized]);
    $row = $statement->fetch();

    return $row ?: null;
}

function cms_public_sitemap_entries(array $settings): array
{
    $entries = [];
    $addEntry = static function (string $loc, string $priority = '0.7', string $changefreq = 'monthly', ?string $lastmod = null) use (&$entries): void {
        $path = '/' . trim($loc, '/');
        $path = $path === '/' ? '/' : $path;
        $entries[$path] = [
            'loc' => cms_absolute_url($path),
            'priority' => $priority,
            'changefreq' => $changefreq,
            'lastmod' => $lastmod,
        ];
    };

    $statement = cms_db()->query(
        "SELECT slug, page_key, page_type, updated_at, published_at
           FROM cms_pages
          WHERE status = 'published' AND is_indexable = 1
          ORDER BY page_type ASC, slug ASC"
    );

    foreach ($statement->fetchAll() as $page) {
        $slug = (string) ($page['slug'] ?? '/');
        $pageKey = (string) ($page['page_key'] ?? '');
        $pageType = (string) ($page['page_type'] ?? 'main');
        $priority = $slug === '/' ? '1.0' : ($pageType === 'local' ? '0.82' : '0.85');
        if (in_array($pageKey, ['vendre', 'acheter', 'estimation', 'fonds-de-commerce', 'secteur', 'contact'], true)) {
            $priority = '0.9';
        }
        $lastmodRaw = (string) (($page['updated_at'] ?? '') ?: ($page['published_at'] ?? ''));
        $lastmod = $lastmodRaw !== '' ? date('Y-m-d', strtotime($lastmodRaw) ?: time()) : null;
        $addEntry($slug, $priority, $slug === '/' ? 'weekly' : 'monthly', $lastmod);
    }

    $addEntry('/histoire', '0.74', 'monthly');
    $addEntry('/avis', '0.76', 'monthly');
    $addEntry('/prestations', '0.88', 'monthly');
    $addEntry('/viager', '0.9', 'monthly');
    $addEntry('/estimation-en-ligne', '0.92', 'weekly');
    $addEntry('/etude-viager-gratuite', '0.9', 'weekly');

    if (cms_is_blog_public_enabled($settings)) {
        $addEntry('/blog', '0.72', 'weekly');
        foreach (cms_public_blog_posts() as $post) {
            if ((int) ($post['is_indexable'] ?? 1) !== 1) {
                continue;
            }
            $dateRaw = (string) (($post['updated_at'] ?? '') ?: ($post['published_at'] ?? '') ?: ($post['created_at'] ?? ''));
            $lastmod = $dateRaw !== '' ? date('Y-m-d', strtotime($dateRaw) ?: time()) : null;
            $addEntry('/blog/' . (string) $post['slug'], '0.68', 'monthly', $lastmod);
        }
    }

    return array_values($entries);
}

function cms_render_sitemap_xml(array $settings): never
{
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (cms_public_sitemap_entries($settings) as $entry) {
        echo "  <url>\n";
        echo '    <loc>' . htmlspecialchars((string) $entry['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";
        if (!empty($entry['lastmod'])) {
            echo '    <lastmod>' . htmlspecialchars((string) $entry['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</lastmod>\n";
        }
        echo '    <changefreq>' . htmlspecialchars((string) $entry['changefreq'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</changefreq>\n";
        echo '    <priority>' . htmlspecialchars((string) $entry['priority'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</priority>\n";
        echo "  </url>\n";
    }
    echo '</urlset>';
    exit;
}

function cms_render_robots_txt(array $settings): never
{
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /app/\n";
    echo "Disallow: /install/\n";
    echo "Disallow: /estimation-track\n";
    echo "\n";
    echo 'Sitemap: ' . cms_absolute_url('/sitemap.xml') . "\n";
    exit;
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