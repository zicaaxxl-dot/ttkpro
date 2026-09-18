<?php
/**
 * Carrega um projeto completo do banco (mesmo JSON do admin).
 */
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/functions.php';
    requireLogin();

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID invalido.']);
        exit;
    }

    $project = getProjectFull($id);
    if (!$project) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Produto nao encontrado.']);
        exit;
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'project' => $project]);
} catch (Throwable $e) {
    error_log('[ttkpro] get_project: ' . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar produto.']);
}
