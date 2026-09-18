<?php
/**
 * ttkpro-editor/includes/config.php
 * Editor de Produto PRO — RZ Tecnologia
 * Configuração central do painel admin
 */

date_default_timezone_set('America/Sao_Paulo');

// ── Acesso ao painel ──────────────────────────────────────────
// Troque por um hash gerado com password_hash() antes de subir para produção.
// Gerar novo hash: php -r "echo password_hash('sua_senha', PASSWORD_DEFAULT);"
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2a$12$DIujPEFyApPryKtiuVal4u7Mu8MQ4/WY8fageZAhlXdS7qTwo3AMC'); // hash de "troque-esta-senha"

// ── Integracao SuperAdmin (legado) ──
if (!defined('SUPERADMIN_API_URL')) {
    define('SUPERADMIN_API_URL', 'https://admin.kryonpay.com/api/verify.php');
}
if (!defined('SUPERADMIN_SHARED_SECRET')) {
    define('SUPERADMIN_SHARED_SECRET', 'ca405dce4cd4d121a380a145411292814659c37a68d6fa076277ae506b95deae');
}

// ── Diretórios ─────────────────────────────────────────────────
define('BASE_DIR', dirname(__DIR__));
define('PROJECTS_DIR', BASE_DIR . '/projects');
define('TEMPLATES_DIR', BASE_DIR . '/templates');
define('EXPORTS_DIR', BASE_DIR . '/exports');

foreach ([PROJECTS_DIR, EXPORTS_DIR] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ── Scraper: limites e segurança ─────────────────────────────
define('SCRAPER_TIMEOUT', 12);          // segundos
define('SCRAPER_MAX_BYTES', 3_000_000); // 3MB de HTML no máximo
define('SCRAPER_USER_AGENT', 'Mozilla/5.0 (compatible; EditorProBot/1.0; +https://rztecnologia.com/bot)');

// ── Sessão ────────────────────────────────────────────────────
function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('epro_admin');
        session_start();
    }
}

function requireLogin(): void
{
    startAdminSession();
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }
}

// ── Helpers ───────────────────────────────────────────────────
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sanitizeText(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', strip_tags($value)));
}