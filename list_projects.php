<?php
/**
 * Lista projetos salvos no banco (para o admin / tela Produtos).
 */
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/functions.php';
    requireLogin();

    $projects = listProjects();
    ob_end_clean();
    echo json_encode(['success' => true, 'projects' => $projects]);
} catch (Throwable $e) {
    error_log('[ttkpro] list_projects: ' . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao listar produtos.']);
}
