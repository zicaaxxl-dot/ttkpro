<?php
/**
 * Webhook PixzyPay
 * Docs: https://docs.pixzypay.com/webhooks/transacao
 * Marca a venda como paga no BANCO (fonte da verdade), mesmo sem arquivo pending.
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

$payloadStatus = (string) ($txPayload['status'] ?? '');
if ($event !== '' && $event !== 'paid' && $payloadStatus !== 'paid') {
    logWebhookPixzy("INFO: Evento nao pago — ignorando");
    http_response_code(200);
    echo 'Received';
    exit;
}

$statusReal = consultarStatusPixzy($lookupId);
logWebhookPixzy("STATUS CONFIRMADO NA API", $statusReal);

$tx = $statusReal['data'] ?? null;
$statusApi = is_array($tx) ? (string) ($tx['status'] ?? '') : '';

// Aceita confirmacao pela API OU pelo webhook (event/status paid),
// para nao deixar o cliente preso se a consulta falhar temporariamente.
$confirmedPaid = (is_array($tx) && $statusApi === 'paid')
    || ($event === 'paid' && $payloadStatus === 'paid');

if (!$confirmedPaid) {
    logWebhookPixzy("STATUS NAO CONFIRMADO COMO PAGO - Ignorando", $statusReal);
    http_response_code(200);
    echo 'Received';
    exit;
}

$transactionId = (string) (
    ($tx['transaction_id'] ?? null)
    ?? ($tx['id'] ?? null)
    ?? $lookupId
);
$safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);
$pendingFile = PENDING_DIR . '/' . $safeId . '.json';
$paidFile = PAID_DIR . '/' . $safeId . '.json';

// Fallback: localizar pending pelo metadata.payment_id interno
if (!file_exists($pendingFile)) {
    $want = $tx['metadata']['payment_id']
        ?? $txPayload['metadata']['payment_id']
        ?? null;
    if (!empty($want)) {
        foreach (glob(PENDING_DIR . '/*.json') ?: [] as $f) {
            $tmp = json_decode(@file_get_contents($f), true);
            if (is_array($tmp) && ($tmp['payment_id'] ?? '') === $want) {
                $pendingFile = $f;
                $safeId = basename($f, '.json');
                $paidFile = PAID_DIR . '/' . $safeId . '.json';
                $transactionId = (string) ($tmp['transaction_id'] ?? $transactionId);
                break;
            }
        }
    }
}

$paymentData = null;
if (file_exists($pendingFile)) {
    $paymentData = json_decode(file_get_contents($pendingFile), true);
}
if (!is_array($paymentData) && file_exists($paidFile)) {
    $paymentData = json_decode(file_get_contents($paidFile), true);
}

$amountCents = (int) ($tx['amount'] ?? $txPayload['amount'] ?? 0);
$amountReais = $amountCents > 0 ? round($amountCents / 100, 2) : 0.0;

if (!is_array($paymentData)) {
    $paymentData = [
        'payment_id' => $tx['metadata']['payment_id']
            ?? $txPayload['metadata']['payment_id']
            ?? $transactionId,
        'transaction_id' => $transactionId,
        'gateway' => 'pixzy',
        'nome' => $tx['client_name'] ?? ($txPayload['client_name'] ?? ''),
        'email' => $tx['client_email'] ?? ($txPayload['client_email'] ?? ''),
        'cpf' => $tx['client_doc'] ?? ($txPayload['client_doc'] ?? ''),
        'phone' => $tx['client_phone'] ?? ($txPayload['client_phone'] ?? ''),
        'valor' => $amountReais,
        'plano' => $tx['metadata']['plano'] ?? ($txPayload['metadata']['plano'] ?? ''),
        'descricao' => '',
        'created_at' => date('Y-m-d H:i:s'),
    ];
}

$alreadyPaidFile = file_exists($paidFile);
$dbRow = getPaymentById($transactionId);
$alreadyPaidDb = $dbRow && ($dbRow['status'] ?? '') === 'paid';

$paymentData['status'] = 'paid';
$paymentData['paid_at'] = date('Y-m-d H:i:s');
$paymentData['webhook_data'] = $payload;
$paymentData['status_confirmado_api'] = $statusReal;
$paymentData['gateway'] = 'pixzy';
$paymentData['transaction_id'] = $transactionId;
if (empty($paymentData['valor']) && $amountReais > 0) {
    $paymentData['valor'] = $amountReais;
}

// Fonte da verdade: banco — mesmo sem arquivo pending (Render free / disco efemero)
$marked = markPaymentPaid($transactionId, [
    'gateway' => 'pixzy',
    'amount' => (float) ($paymentData['valor'] ?? $amountReais),
    'nome' => $paymentData['nome'] ?? '',
    'email' => $paymentData['email'] ?? '',
    'cpf' => $paymentData['cpf'] ?? '',
    'webhook' => $payload,
]);
logWebhookPixzy($marked ? 'DB: pagamento marcado como paid' : 'DB: falha ao marcar paid', [
    'transaction_id' => $transactionId,
]);

// Tambem marca pelo id numerico do webhook, se diferente (check-payment pode usar qualquer um)
if ((string) $lookupId !== (string) $transactionId) {
    markPaymentPaid((string) $lookupId, [
        'gateway' => 'pixzy',
        'amount' => (float) ($paymentData['valor'] ?? $amountReais),
        'nome' => $paymentData['nome'] ?? '',
        'email' => $paymentData['email'] ?? '',
        'cpf' => $paymentData['cpf'] ?? '',
        'alias_of' => $transactionId,
    ]);
}

@file_put_contents($paidFile, json_encode($paymentData, JSON_PRETTY_PRINT));
if (file_exists($pendingFile)) {
    @unlink($pendingFile);
}

if (!$alreadyPaidFile && !$alreadyPaidDb) {
    $emailResult = enviarEmailConfirmacao($paymentData);
    logWebhookPixzy($emailResult ? 'EMAIL ENVIADO' : 'ERRO EMAIL');
} else {
    logWebhookPixzy('INFO: Transacao ja processada (idempotente) — email nao reenviado');
}

logWebhookPixzy("========== FIM WEBHOOK PIXZY ==========\n");

http_response_code(200);
echo 'OK';
