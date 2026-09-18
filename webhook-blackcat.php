<?php
/**
 * Webhook para receber notificações da BlackCat
 * Docs: https://docs.blackcatoficial.com/#webhook-events
 *
 * A BlackCat não documenta assinatura/HMAC no payload do webhook.
 * Por segurança, seguimos o mesmo padrão já usado no webhook-nexypay.php:
 * nunca confiamos só no payload recebido — reconfirmamos o status real
 * direto na API (GET /sales/{id}/status) antes de liberar o pagamento.
 */

ob_start();
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60');
ini_set('display_errors', 0);
error_reporting(0);

function logWebhookBlackcat($msg, $data = null)
{
    $log = "[" . date('Y-m-d H:i:s') . "] [IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "] $msg";
    if ($data)
        $log .= " | Dados: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @file_put_contents(__DIR__ . '/webhook-blackcat.log', $log . "\n", FILE_APPEND);
}

logWebhookBlackcat("========== NOVA REQUISICAO WEBHOOK BLACKCAT ==========");

require_once 'config.php';
require_once 'gateway-helpers.php';
require_once 'email-helper.php';

$input = file_get_contents('php://input');
$payload = json_decode($input, true);

$headerEvent = $_SERVER['HTTP_X_WEBHOOK_EVENT'] ?? '';
$headerSource = $_SERVER['HTTP_X_WEBHOOK_SOURCE'] ?? '';

logWebhookBlackcat("WEBHOOK: Dados recebidos", $payload);
logWebhookBlackcat("Headers", ['event' => $headerEvent, 'source' => $headerSource]);

if (!$payload || empty($payload['transactionId'])) {
    logWebhookBlackcat("ERRO: Payload invalido ou sem transactionId");
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$transactionId = $payload['transactionId'];
$eventoRecebido = $payload['event'] ?? $headerEvent;

logWebhookBlackcat("transactionId: $transactionId | evento recebido: $eventoRecebido");

// Só nos interessa o evento de pagamento confirmado
if ($eventoRecebido !== 'transaction.paid') {
    logWebhookBlackcat("EVENTO: $eventoRecebido - Ignorando (não é transaction.paid)");
    http_response_code(200);
    echo 'Received';
    exit;
}

// IMPORTANTE: nunca confiar só no payload do webhook — reconfirmamos via API.
$statusReal = consultarStatusBlackCat($transactionId);

logWebhookBlackcat("STATUS CONFIRMADO NA API", $statusReal);

if (empty($statusReal['success']) || empty($statusReal['data']['status']) || $statusReal['data']['status'] !== 'PAID') {
    logWebhookBlackcat("STATUS NAO CONFIRMADO COMO PAGO - Ignorando", $statusReal);
    http_response_code(200);
    echo 'Received';
    exit;
}

$safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);
$pendingFile = PENDING_DIR . '/' . $safeId . '.json';
$paidFile = PAID_DIR . '/' . $safeId . '.json';

// Idempotência: se já processamos essa transação, apenas responde OK.
if (file_exists($paidFile)) {
    logWebhookBlackcat("INFO: Transacao ja processada anteriormente (idempotente)");
    http_response_code(200);
    echo 'OK';
    exit;
}

logWebhookBlackcat("ARQUIVO: Buscando pending", ['path' => $pendingFile, 'exists' => file_exists($pendingFile)]);

if (!file_exists($pendingFile)) {
    logWebhookBlackcat("AVISO: Pagamento nao encontrado em pending");
    http_response_code(200);
    echo 'Payment not found';
    exit;
}

$paymentData = json_decode(file_get_contents($pendingFile), true);
logWebhookBlackcat("DADOS: Pagamento carregado", $paymentData);

$paymentData['status'] = 'paid';
$paymentData['paid_at'] = $payload['paidAt'] ?? date('Y-m-d H:i:s');
$paymentData['webhook_data'] = $payload;
$paymentData['status_confirmado_api'] = $statusReal;

file_put_contents($paidFile, json_encode($paymentData, JSON_PRETTY_PRINT));
logWebhookBlackcat("ARQUIVO: Salvo em paid", ['path' => $paidFile]);

unlink($pendingFile);
logWebhookBlackcat("ARQUIVO: Removido de pending");

logWebhookBlackcat("EMAIL: Iniciando envio");
$emailResult = enviarEmailConfirmacao($paymentData);
$emailStatus = $emailResult ? 'EMAIL ENVIADO COM SUCESSO' : 'ERRO AO ENVIAR EMAIL';
logWebhookBlackcat("RESULTADO: $emailStatus");
logWebhookBlackcat("========== FIM WEBHOOK BLACKCAT ==========\n");

http_response_code(200);
echo 'OK';