<?php
/**
 * Webhook para receber notificações de depósito da MisticPay
 * Docs: https://docs.misticpay.com/#webhook-deposit
 *
 * A MisticPay não documenta assinatura/HMAC no payload do webhook.
 * Por segurança, seguimos o mesmo padrão do webhook-nexypay.php:
 * nunca confiamos só no payload recebido — reconfirmamos o status
 * real direto na API (POST /api/transactions/check) antes de
 * liberar o pagamento.
 */

ob_start();
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60');
ini_set('display_errors', 0);
error_reporting(0);

function logWebhookMisticpay($msg, $data = null)
{
    $log = "[" . date('Y-m-d H:i:s') . "] [IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "] $msg";
    if ($data)
        $log .= " | Dados: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @file_put_contents(__DIR__ . '/webhook-misticpay.log', $log . "\n", FILE_APPEND);
}

logWebhookMisticpay("========== NOVA REQUISICAO WEBHOOK MISTICPAY ==========");

require_once 'config.php';
require_once 'gateway-helpers.php';
require_once 'email-helper.php';

$input = file_get_contents('php://input');
$payload = json_decode($input, true);

logWebhookMisticpay("WEBHOOK: Dados recebidos", $payload);

if (!$payload || empty($payload['transactionId'])) {
    logWebhookMisticpay("ERRO: Payload invalido ou sem transactionId");
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$transactionId = (string) $payload['transactionId'];
$transactionType = $payload['transactionType'] ?? '';
$statusRecebido = $payload['status'] ?? '';

logWebhookMisticpay("transactionId: $transactionId | tipo: $transactionType | status recebido no payload: $statusRecebido");

// Só nos interessa depósito (cash-in). Saques e MEDs não liberam acesso aqui.
if ($transactionType !== 'DEPOSITO') {
    logWebhookMisticpay("TIPO: $transactionType - Ignorando (não é DEPOSITO)");
    http_response_code(200);
    echo 'Received';
    exit;
}

// IMPORTANTE: nunca confiar só no payload do webhook — reconfirmamos via API.
$statusReal = consultarStatusMisticPay($transactionId);

logWebhookMisticpay("STATUS CONFIRMADO NA API", $statusReal);

if (empty($statusReal['transaction']['transactionState']) || $statusReal['transaction']['transactionState'] !== 'COMPLETO') {
    logWebhookMisticpay("STATUS NAO CONFIRMADO COMO PAGO - Ignorando", $statusReal);
    http_response_code(200);
    echo 'Received';
    exit;
}

$safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);
$pendingFile = PENDING_DIR . '/' . $safeId . '.json';
$paidFile = PAID_DIR . '/' . $safeId . '.json';

// Idempotência: se já processamos essa transação, apenas responde OK.
if (file_exists($paidFile)) {
    logWebhookMisticpay("INFO: Transacao ja processada anteriormente (idempotente)");
    http_response_code(200);
    echo 'OK';
    exit;
}

logWebhookMisticpay("ARQUIVO: Buscando pending", ['path' => $pendingFile, 'exists' => file_exists($pendingFile)]);

if (!file_exists($pendingFile)) {
    logWebhookMisticpay("AVISO: Pagamento nao encontrado em pending");
    http_response_code(200);
    echo 'Payment not found';
    exit;
}

$paymentData = json_decode(file_get_contents($pendingFile), true);
logWebhookMisticpay("DADOS: Pagamento carregado", $paymentData);

$paymentData['status'] = 'paid';
$paymentData['paid_at'] = date('Y-m-d H:i:s');
$paymentData['webhook_data'] = $payload;
$paymentData['status_confirmado_api'] = $statusReal;

file_put_contents($paidFile, json_encode($paymentData, JSON_PRETTY_PRINT));
logWebhookMisticpay("ARQUIVO: Salvo em paid", ['path' => $paidFile]);

unlink($pendingFile);
logWebhookMisticpay("ARQUIVO: Removido de pending");

logWebhookMisticpay("EMAIL: Iniciando envio");
$emailResult = enviarEmailConfirmacao($paymentData);
$emailStatus = $emailResult ? 'EMAIL ENVIADO COM SUCESSO' : 'ERRO AO ENVIAR EMAIL';
logWebhookMisticpay("RESULTADO: $emailStatus");
logWebhookMisticpay("========== FIM WEBHOOK MISTICPAY ==========\n");

http_response_code(200);
echo 'OK';