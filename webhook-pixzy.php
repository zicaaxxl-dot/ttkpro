<?php
/**
 * Webhook PixzyPay
 * Docs: https://docs.pixzypay.com/webhooks/transacao
 * Reconfirma status via GET /transactions/{id} antes de liberar.
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

function logWebhookPixzy($msg, $data = null)
{
    $log = "[" . date('Y-m-d H:i:s') . "] [IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "] $msg";
    if ($data !== null) {
        $log .= " | Dados: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    @file_put_contents(__DIR__ . '/webhook-pixzy.log', $log . "\n", FILE_APPEND);
}

logWebhookPixzy("========== NOVA REQUISICAO WEBHOOK PIXZY ==========");

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gateway-helpers.php';
require_once __DIR__ . '/email-helper.php';

$input = file_get_contents('php://input');
$payload = json_decode($input, true);
logWebhookPixzy("WEBHOOK: Dados recebidos", $payload);

$event = $payload['event'] ?? '';
$txPayload = $payload['transaction'] ?? null;

if (!$payload || !is_array($txPayload) || empty($txPayload['id'])) {
    logWebhookPixzy("ERRO: Payload invalido");
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$lookupId = (string) $txPayload['id'];
logWebhookPixzy("event=$event lookupId=$lookupId");

if ($event !== '' && $event !== 'paid' && ($txPayload['status'] ?? '') !== 'paid') {
    logWebhookPixzy("INFO: Evento nao pago — ignorando");
    http_response_code(200);
    echo 'Received';
    exit;
}

$statusReal = consultarStatusPixzy($lookupId);
logWebhookPixzy("STATUS CONFIRMADO NA API", $statusReal);

$tx = $statusReal['data'] ?? null;
$statusApi = is_array($tx) ? ($tx['status'] ?? '') : '';

if (!is_array($tx) || $statusApi !== 'paid') {
    logWebhookPixzy("STATUS NAO CONFIRMADO COMO PAGO - Ignorando", $statusReal);
    http_response_code(200);
    echo 'Received';
    exit;
}

$transactionId = (string) ($tx['id'] ?? $lookupId);
$safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);
$pendingFile = PENDING_DIR . '/' . $safeId . '.json';
$paidFile = PAID_DIR . '/' . $safeId . '.json';

if (!file_exists($pendingFile) && !empty($tx['metadata']['payment_id'])) {
    $want = $tx['metadata']['payment_id'];
    foreach (glob(PENDING_DIR . '/*.json') ?: [] as $f) {
        $tmp = json_decode(@file_get_contents($f), true);
        if (is_array($tmp) && ($tmp['payment_id'] ?? '') === $want) {
            $pendingFile = $f;
            $safeId = basename($f, '.json');
            $paidFile = PAID_DIR . '/' . $safeId . '.json';
            $transactionId = $tmp['transaction_id'] ?? $transactionId;
            break;
        }
    }
}

if (file_exists($paidFile)) {
    logWebhookPixzy("INFO: Transacao ja processada (idempotente)");
    http_response_code(200);
    echo 'OK';
    exit;
}

if (!file_exists($pendingFile)) {
    logWebhookPixzy("AVISO: Pagamento nao encontrado em pending", ['safeId' => $safeId]);
    http_response_code(200);
    echo 'Payment not found';
    exit;
}

$paymentData = json_decode(file_get_contents($pendingFile), true);
$paymentData['status'] = 'paid';
$paymentData['paid_at'] = date('Y-m-d H:i:s');
$paymentData['webhook_data'] = $payload;
$paymentData['status_confirmado_api'] = $statusReal;

file_put_contents($paidFile, json_encode($paymentData, JSON_PRETTY_PRINT));
@unlink($pendingFile);

$emailResult = enviarEmailConfirmacao($paymentData);
logWebhookPixzy($emailResult ? 'EMAIL ENVIADO' : 'ERRO EMAIL');
logWebhookPixzy("========== FIM WEBHOOK PIXZY ==========\n");

http_response_code(200);
echo 'OK';