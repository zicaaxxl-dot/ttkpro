<?php
/**
 * save_gateway.php
 * Endpoint chamado pelo admin.php (fetch) sempre que o usuário mexe
 * na aba "Pagamento". Grava o gateway ativo direto no banco.
 *
 * IMPORTANTE: qualquer warning/notice do PHP normalmente é impresso
 * ANTES do echo json_encode(...), o que quebra o JSON e faz o
 * fetch().json() do navegador falhar com "erro de conexão" (mesmo
 * a requisição tendo chegado certinho no servidor). Por isso aqui
 * usamos ob_start() para capturar qualquer saída indevida e nunca
 * deixar nada além do JSON puro sair pela resposta.
 */

ob_start();                 // captura qualquer output acidental (warnings, notices, etc.)
ini_set('display_errors', '0');
error_reporting(E_ALL);     // continua logando os erros...
ini_set('log_errors', '1'); // ...só que no log do servidor, não na resposta

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/functions.php';

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'JSON inválido recebido.']);
        exit;
    }

    $provider = $input['provider'] ?? 'nexypay';
    $public   = $input['public_key'] ?? '';
    $secret   = $input['secret_key'] ?? '';

    saveGatewaySettings($provider, $public, $secret);

    ob_end_clean(); // descarta qualquer lixo que tenha sido impresso até aqui
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    error_log('[ttkpro] save_gateway.php: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar gateway.',
        // debug_temp: remova esta linha depois de resolver o problema
        'debug_temp' => $e->getMessage(),
    ]);
}