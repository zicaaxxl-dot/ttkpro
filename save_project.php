<?php
/**
 * save_project.php
 * Endpoint chamado pelo admin.php ao clicar em "Salvar" / "Baixar Site".
 * Persiste o projeto inteiro no banco (além do localStorage, que continua
 * funcionando como cache local/offline).
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/functions.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload de projeto inválido.']);
    exit;
}

try {
    $projectId = saveProject($data);

    // Se o admin marcar este projeto como o que deve ir ao ar, o front
    // pode mandar "activate": true junto no mesmo payload.
    if (!empty($data['activate'])) {
        setActiveProject($projectId);
    }

    echo json_encode(['success' => true, 'id' => $projectId]);
} catch (Throwable $e) {
    error_log('[ttkpro] save_project: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar projeto.']);
}