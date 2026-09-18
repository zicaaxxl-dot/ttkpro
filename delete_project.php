<?php
/**
 * Remove um projeto do banco.
 */
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/functions.php';
    requireLogin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metodo nao permitido.']);
        exit;
    }

    $raw = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($raw['id'] ?? ($_POST['id'] ?? 0));
    if ($id <= 0) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID invalido.']);
        exit;
    }

    $ok = deleteProject($id);
    ob_end_clean();
    echo json_encode(['success' => (bool) $ok]);
} catch (Throwable $e) {
    error_log('[ttkpro] delete_project: ' . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir produto.']);
}
