<?php
/**
 * publish.php — salva o projeto no banco e devolve a URL publica da pagina.
 */
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo nao permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload de projeto invalido.']);
    exit;
}

try {
    // Garante UNIQUE em name (precisa do ON DUPLICATE KEY do saveProject)
    try {
        db()->exec('ALTER TABLE projects ADD UNIQUE KEY uniq_project_name (name)');
    } catch (Throwable $e) {
        // ja existe — ok
    }

    $projectId = saveProject($data);
    setActiveProject($projectId);

    $slug = slugifyProjectName($data['name']);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim($protocol . '://' . $host, '/');

    $urlPretty = $base . '/p/' . rawurlencode($slug);
    $urlFallback = $base . '/p.php?id=' . $projectId;

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'id' => $projectId,
        'slug' => $slug,
        'url' => $urlPretty,
        'url_fallback' => $urlFallback,
    ]);
} catch (Throwable $e) {
    error_log('[ttkpro] publish: ' . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao publicar pagina.', 'debug' => $e->getMessage()]);
}