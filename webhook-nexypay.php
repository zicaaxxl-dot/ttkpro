<?php
/**
 * Webhook para receber notificações da QuantiumPay
 */

ob_start();
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60');
ini_set('display_errors', 0);
error_reporting(0);

function logWebhookNexy($msg, $data = null)
{
    $log = "[" . date('Y-m-d H:i:s') . "] [IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "] $msg";
    if ($data)
        $log .= " | Dados: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @file_put_contents(__DIR__ . '/webhook-nexypay.log', $log . "\n", FILE_APPEND);
}

logWebhookNexy("========== NOVA REQUISICAO WEBHOOK NEXYPAY ==========");

require_once 'config.php';
require_once 'email-helper.php';

$input = file_get_contents('php://input');
$payload = json_decode($input, true);

logWebhookNexy("WEBHOOK: Dados recebidos", $payload);

if (!$payload || empty($payload['idTransaction'])) {
    logWebhookNexy("ERRO: Payload invalido ou sem idTransaction");
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$idTransaction = $payload['idTransaction'];
$statusRecebido = $payload['status'] ?? '';

logWebhookNexy("idTransaction: $idTransaction | status recebido no payload: $statusRecebido");

// IMPORTANTE (conforme doc QuantiumPay): nunca confiar só no payload do webhook,
// pois pode ser forjado por terceiros que descobrirem a URL.
// Confirmamos sempre via POST /api/v1/webhook/.
$statusReal = consultarStatusQuantiumPay($idTransaction);

logWebhookNexy("STATUS CONFIRMADO NA API", $statusReal);

if (empty($statusReal['status']) || !in_array($statusReal['status'], ['PAID_OUT', 'PAID'], true)) {
    logWebhookNexy("STATUS NAO CONFIRMADO COMO PAGO - Ignorando", $statusReal);
    http_response_code(200);
    echo 'Received';
    exit;
}

$safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $idTransaction);
$pendingFile = PENDING_DIR . '/' . $safeId . '.json';
$paidFile = PAID_DIR . '/' . $safeId . '.json';

// Idempotência: se já processamos essa transação, apenas responde OK.
if (file_exists($paidFile)) {
    logWebhookNexy("INFO: Transacao ja processada anteriormente (idempotente)");
    http_response_code(200);
    echo 'OK';
    exit;
}

logWebhookNexy("ARQUIVO: Buscando pending", ['path' => $pendingFile, 'exists' => file_exists($pendingFile)]);

if (!file_exists($pendingFile)) {
    logWebhookNexy("AVISO: Pagamento nao encontrado em pending");
    http_response_code(200);
    echo 'Payment not found';
    exit;
}

$paymentData = json_decode(file_get_contents($pendingFile), true);
logWebhookNexy("DADOS: Pagamento carregado", $paymentData);

$paymentData['status'] = 'paid';
$paymentData['paid_at'] = $payload['paid_at'] ?? date('Y-m-d H:i:s');
$paymentData['webhook_data'] = $payload;
$paymentData['status_confirmado_api'] = $statusReal;

file_put_contents($paidFile, json_encode($paymentData, JSON_PRETTY_PRINT));
logWebhookNexy("ARQUIVO: Salvo em paid", ['path' => $paidFile]);

unlink($pendingFile);
logWebhookNexy("ARQUIVO: Removido de pending");

logWebhookNexy("EMAIL: Iniciando envio");
$emailResult = enviarEmailConfirmacao($paymentData);
$emailStatus = $emailResult ? 'EMAIL ENVIADO COM SUCESSO' : 'ERRO AO ENVIAR EMAIL';
logWebhookNexy("RESULTADO: $emailStatus");
logWebhookNexy("========== FIM WEBHOOK NEXYPAY ==========\n");

http_response_code(200);
echo 'OK';

/**
 * Confirma o status real da transação direto na API da QuantiumPay.
 */
function consultarStatusQuantiumPay($idTransaction)
{
    $payload = [
        'api-key' => NEXYPAY_API_KEY,
        'idtransaction' => $idTransaction,
    ];

    $ch = curl_init('https://app.quantiumpay.com/api/v1/webhook/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['status' => null, 'error' => $curlError];
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : ['status' => null, 'raw' => $response];
}

function buildWebhookNexypayPhp(): string
{
    return <<<'PHP'
<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/config.php';

function logWhN(string $msg, $data = null): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($data !== null) $line .= ' | ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    @file_put_contents(__DIR__ . '/webhook-nexypay.log', $line . "\n", FILE_APPEND);
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?? [];
logWhN('Payload recebido', $data);

$txId   = $data['idTransaction'] ?? $data['transaction_id'] ?? $data['id'] ?? '';
$status = strtolower($data['status'] ?? $data['paymentStatus'] ?? '');

$safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $txId);

if (!in_array($status, ['paid', 'success', 'approved', 'completed'], true) || !$safeId) {
    http_response_code(200);
    echo 'Received';
    exit;
}

$pendingFile = PENDING_DIR . '/' . $safeId . '.json';
$paidFile    = PAID_DIR    . '/' . $safeId . '.json';

if (!file_exists($pendingFile)) {
    logWhN('Pendente não encontrado: ' . $safeId);
    http_response_code(200);
    echo 'Not found';
    exit;
}

$pay            = json_decode(file_get_contents($pendingFile), true);
$pay['status']  = 'paid';
$pay['paid_at'] = date('Y-m-d H:i:s');
$pay['webhook'] = $data;

file_put_contents($paidFile, json_encode($pay, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
unlink($pendingFile);
logWhN('Pago: ' . $safeId);

http_response_code(200);
echo 'OK';
PHP;
}