<?php
/**
 * save_project.php — persiste o projeto no banco (fonte da verdade no plano pago).
 */
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/functions.php';
    requireLogin();

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data) || empty($data['name'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payload de projeto inválido.']);
        exit;
    }

    $projectId = saveProject($data);

    if (!empty($data['activate'])) {
        setActiveProject($projectId);
    }

    $slug = slugifyProjectName((string) $data['name']);
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'id' => $projectId,
        'slug' => $slug,
        'url' => '/p/' . $slug,
    ]);
} catch (Throwable $e) {
    error_log('[ttkpro] save_project: ' . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar projeto.', 'debug' => $e->getMessage()]);
}
