<?php
/**
 * Retorna o gateway ativo do banco para o admin preencher os campos.
 */
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/functions.php';
    requireLogin();

    ensureProjectTablesSchema();
    $gw = getGatewaySettings();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'data' => [
            'provider' => $gw['provider'] ?? 'pixzy',
            'public_key' => $gw['public_key'] ?? '',
            'secret_key' => $gw['secret_key'] ?? '',
        ],
    ]);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar gateway.']);
}
