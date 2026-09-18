<?php

/**
 * export.php — Compilador ZIP do Editor de Produto PRO
 * RZ Tecnologia / RenanDev
 *
 * Recebe o JSON do projeto via POST, processa os templates,
 * injeta credenciais do gateway, e devolve um .zip pronto
 * para hospedar em qualquer servidor PHP compartilhado.
 *
 * Gateways suportados no pacote gerado: Ecompag, QuantiumPay (NexyPay),
 * BlackCat e MisticPay.
 */

/* ═══════════════════════════════════════════════════════════════
   BOOTSTRAP
═══════════════════════════════════════════════════════════════ */
ob_start();

ini_set('display_errors', 0);
error_reporting(0);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '120');

require_once __DIR__ . '/includes/config.php';
requireLogin();

/* ═══════════════════════════════════════════════════════════════
   VALIDAÇÃO DA REQUISIÇÃO
═══════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Método não permitido']));
}

$raw = file_get_contents('php://input');
$proj = json_decode($raw, true);

if (!$proj || !isset($proj['basico'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Payload inválido']));
}

/* ═══════════════════════════════════════════════════════════════
   EXTRAÇÃO DE DADOS DO PROJETO
═══════════════════════════════════════════════════════════════ */
$basico = $proj['basico'] ?? [];
$gateway = $proj['gateway'] ?? [];
$orderBumps = $proj['orderBumps'] ?? [];
$reviews = $proj['reviews'] ?? [];
$recs = $proj['recommendations'] ?? $proj['recs'] ?? [];
$projectName = preg_replace('/[^a-z0-9\-_]/i', '-', $proj['name'] ?? 'produto');

// Dados do produto
$title = $basico['title'] ?? 'Produto';
$badge = $basico['badge'] ?? '';
$price = (float) ($basico['price'] ?? 0);
$priceOrig = (float) ($basico['price_original'] ?? 0);
$description = $basico['description'] ?? '';
$images = array_values(array_filter($basico['images'] ?? []));
$ownCheckout = (bool) ($basico['own_checkout'] ?? true);
$externalLink = $basico['external_link'] ?? '';

// Gateway
// Providers aceitos: 'ecompag', 'nexypay', 'blackcat', 'misticpay'
$gwProvider = $gateway['provider'] ?? 'ecompag';
$gwPublic = $gateway['public_key'] ?? '';
$gwSecret = $gateway['secret_key'] ?? '';

// Derived
$siteUrl = ''; // será o domínio do lojista — deixamos placeholder
$webhookUrl = '__SITE_URL__/webhook.php';

/* ═══════════════════════════════════════════════════════════════
   HELPER: lê template relativo a este arquivo
═══════════════════════════════════════════════════════════════ */
function readTemplate(string $path): string
{
    $full = __DIR__ . '/' . $path;
    if (!file_exists($full)) {
        throw new RuntimeException("Template não encontrado: $path");
    }
    return file_get_contents($full);
}

/* ═══════════════════════════════════════════════════════════════
   BLOCO DE DADOS INJETADO NOS HTMLS
═══════════════════════════════════════════════════════════════ */
function buildDataScript(array $proj, bool $standalone = true): string
{
    $json = json_encode($proj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    $autoRender = $standalone
        ? "window.addEventListener('DOMContentLoaded', function() {
            window.PROJECT = window.EPRO;
            try { PROJECT = window.EPRO; } catch(e) {} // <-- atribui na variável REAL usada pelos botões
            if (typeof render === 'function') render(window.EPRO);
            if (typeof renderProject === 'function') renderProject(window.EPRO);
        });"
        : '';

    return "<script>\n/* EPRO — dados injetados pelo compilador */\nwindow.EPRO = $json;\n$autoRender\n</script>";
}

/* ═══════════════════════════════════════════════════════════════
   PROCESSADOR DE TEMPLATE HTML
═══════════════════════════════════════════════════════════════ */
function processHtml(string $html, array $proj, string $dataScript): string
{
    $html = preg_replace('/<head([^>]*)>/i', "<head$1>\n$dataScript", $html, 1);
    $html = preg_replace('/<div class="placeholder-note"[^>]*>.*?<\/div>/si', '', $html);
    return $html;
}

/* ═══════════════════════════════════════════════════════════════
   GERA config.php DO PACOTE (credenciais do lojista injetadas)
═══════════════════════════════════════════════════════════════ */
function buildConfigPhp(
    string $provider,
    string $publicKey,
    string $secretKey,
    string $projectName
): string {
    $now = date('Y-m-d H:i:s');
    $providerEsc = addslashes($provider);
    $publicEsc = addslashes($publicKey);
    $secretEsc = addslashes($secretKey);
    return <<<PHP
<?php
date_default_timezone_set('America/Sao_Paulo');
/**
 * config.php — Gerado automaticamente pelo Editor de Produto PRO
 * Projeto : {$projectName}
 * Gerado  : {$now}
 * Gateway : {$provider}
 */
define('ACTIVE_GATEWAY', '{$providerEsc}');
define('ECOMPAG_CLIENT_ID',     '{$publicEsc}');
define('ECOMPAG_CLIENT_SECRET', '{$secretEsc}');
define('NEXYPAY_API_KEY', !empty('{$secretEsc}') ? '{$secretEsc}' : '{$publicEsc}');
define('BLACKCAT_API_KEY', !empty('{$secretEsc}') ? '{$secretEsc}' : '{$publicEsc}');
define('MISTICPAY_CLIENT_ID',     '{$publicEsc}');
define('MISTICPAY_CLIENT_SECRET', '{$secretEsc}');

\$protocol = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
\$host = \$_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', rtrim(\$protocol . '://' . \$host, '/'));

define('WEBHOOK_URL_ECOMPAG',   SITE_URL . '/webhook.php');
define('WEBHOOK_URL_NEXYPAY',   SITE_URL . '/webhook-nexypay.php');
define('WEBHOOK_URL_BLACKCAT',  SITE_URL . '/webhook-blackcat.php');
define('WEBHOOK_URL_MISTICPAY', SITE_URL . '/webhook-misticpay.php');
define('WEBHOOK_URL', WEBHOOK_URL_ECOMPAG);
define('ACCESS_LINK', SITE_URL . '/obrigado.html');

define('PAYMENTS_DIR', __DIR__ . '/payments');
define('PENDING_DIR',  PAYMENTS_DIR . '/pending');
define('PAID_DIR',     PAYMENTS_DIR . '/paid');

define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USER',      'seuemail@gmail.com');
define('SMTP_PASSWORD',  'sua_senha_de_app');
define('EMAIL_FROM',     'seuemail@gmail.com');
define('EMAIL_FROM_NAME','Loja');
define('EMAIL_SUBJECT',  'Pagamento Confirmado — Acesse seu produto!');
define('ACCESS_TOKEN_SALT', '{$projectName}_' . bin2hex(random_bytes(8)));

foreach ([PAYMENTS_DIR, PENDING_DIR, PAID_DIR] as \$dir) {
    if (!is_dir(\$dir)) mkdir(\$dir, 0750, true);
}

function generateUniqueId(): string {
    return uniqid('payment_', true) . '_' . time();
}

function sanitize(string \$value): string {
    return htmlspecialchars(strip_tags(trim(\$value)), ENT_QUOTES, 'UTF-8');
}

function validarEmail(string \$email): bool {
    return filter_var(\$email, FILTER_VALIDATE_EMAIL) !== false;
}

function validarCPF(string \$cpf): bool {
    \$cpf = preg_replace('/[^0-9]/', '', \$cpf);
    if (strlen(\$cpf) !== 11 || preg_match('/^(\d)\1+$/', \$cpf)) return false;
    \$sum = 0;
    for (\$i = 1; \$i <= 9; \$i++) \$sum += (int)\$cpf[\$i - 1] * (11 - \$i);
    \$rem = (\$sum * 10) % 11; if (\$rem >= 10) \$rem = 0;
    if (\$rem !== (int)\$cpf[9]) return false;
    \$sum = 0;
    for (\$i = 1; \$i <= 10; \$i++) \$sum += (int)\$cpf[\$i - 1] * (12 - \$i);
    \$rem = (\$sum * 10) % 11; if (\$rem >= 10) \$rem = 0;
    return \$rem === (int)\$cpf[10];
}
PHP;
}

/* ═══════════════════════════════════════════════════════════════
   GERA api-pix.php
═══════════════════════════════════════════════════════════════ */
function buildApiPixPhp(): string
{
    return <<<'PHP'
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['success' => false, 'message' => 'Dados inválidos']); exit; }

$nome  = sanitize($data['name']  ?? '');
$email = sanitize($data['email'] ?? '');
$cpf   = preg_replace('/[^0-9]/', '', $data['cpf']   ?? '');
$phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
$valor = floatval($data['amount'] ?? 0);
$produto = sanitize($data['product'] ?? 'Produto');
$bumps = $data['bumps'] ?? [];

$errors = [];
if (empty($nome) || strlen($nome) < 3) $errors[] = 'Nome inválido';
if (!validarEmail($email))             $errors[] = 'Email inválido';
if (!validarCPF($cpf))                 $errors[] = 'CPF inválido';
if ($valor <= 0)                       $errors[] = 'Valor inválido';

if ($errors) {
    echo json_encode(['success' => false, 'message' => 'Erro de validação', 'errors' => $errors]);
    exit;
}

$descricao = $produto;
if (!empty($bumps)) {
    $descricao .= ' + ' . implode(', ', array_map(fn($b) => $b['name'] ?? '', $bumps));
}

if (ACTIVE_GATEWAY === 'nexypay') {
    $result = gerarPixQuantiumPay($nome, $email, $cpf, $phone, $valor, $descricao);
} elseif (ACTIVE_GATEWAY === 'blackcat') {
    $result = gerarPixBlackCat($nome, $email, $cpf, $phone, $valor, $descricao);
} elseif (ACTIVE_GATEWAY === 'misticpay') {
    $result = gerarPixMisticPay($nome, $email, $cpf, $phone, $valor, $descricao);
} else {
    $result = gerarPixEcompag($nome, $email, $cpf, $phone, $valor, $descricao);
}

echo json_encode($result);
exit;

function gerarPixQuantiumPay($nome, $email, $cpf, $phone, $valor, $descricao) {
    $postData = [
        'api-key' => NEXYPAY_API_KEY,
        'amount'  => round($valor, 2),
        'method'  => 'pix',
        'client'  => ['name' => $nome, 'document' => $cpf, 'email' => $email, 'telefone' => $phone],
        'notification_url' => WEBHOOK_URL_NEXYPAY,
    ];
    $ch = curl_init('https://app.quantiumpay.com/api/v1/gateway/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) return ['success' => false, 'message' => 'Erro ao conectar (QuantiumPay): ' . $err];
    $r = json_decode($response, true);
    
    if ($httpCode === 200 && ($r['status'] ?? '') === 'success' && !empty($r['idTransaction'])) {
        $transactionId = $r['idTransaction'];
        $safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);
        $payment = [
            'payment_id' => generateUniqueId(), 'transaction_id' => $transactionId,
            'gateway' => 'nexypay', 'status' => 'pending',
            'nome' => $nome, 'email' => $email, 'cpf' => $cpf, 'phone' => $phone,
            'valor' => $valor, 'descricao' => $descricao,
            'qrcode' => $r['paymentCode'], 'qrcode_base64' => $r['paymentCodeBase64'] ?? null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents(PENDING_DIR . '/' . $safeId . '.json', json_encode($payment, JSON_PRETTY_PRINT));
        return [
            'success' => true, 'transaction_id' => $transactionId,
            'qrcode' => $r['paymentCode'], 'qrcode_base64' => $r['paymentCodeBase64'] ?? null,
            'amount' => $valor,
        ];
    }
    return ['success' => false, 'message' => $r['message'] ?? ($r['error'] ?? 'Erro ao gerar PIX (QuantiumPay)')];
}

function gerarPixEcompag($nome, $email, $cpf, $phone, $valor, $descricao) {
    $postData = [
        'client_id' => ECOMPAG_CLIENT_ID, 'client_secret' => ECOMPAG_CLIENT_SECRET,
        'nome' => $nome, 'cpf' => $cpf, 'valor' => $valor,
        'descricao' => $descricao, 'urlnoty' => WEBHOOK_URL_ECOMPAG,
    ];
    $ch = curl_init('https://api.ecompag.com/v2/pix/qrcode.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) return ['success' => false, 'message' => 'Erro ao conectar (Ecompag): ' . $err];
    $r = json_decode($response, true);
    
    if ($httpCode === 200 && isset($r['qrcode'])) {
        $transactionId = $r['transactionId'];
        $payment = [
            'payment_id' => generateUniqueId(), 'transaction_id' => $transactionId,
            'gateway' => 'ecompag', 'status' => 'pending',
            'nome' => $nome, 'email' => $email, 'cpf' => $cpf, 'phone' => $phone,
            'valor' => $valor, 'descricao' => $descricao, 'qrcode' => $r['qrcode'],
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents(PENDING_DIR . '/' . $transactionId . '.json', json_encode($payment, JSON_PRETTY_PRINT));
        return ['success' => true, 'transaction_id' => $transactionId, 'qrcode' => $r['qrcode'], 'amount' => $valor];
    }
    return ['success' => false, 'message' => $r['message'] ?? 'Erro ao gerar PIX (Ecompag)'];
}

function gerarPixBlackCat($nome, $email, $cpf, $phone, $valor, $descricao) {
    $postData = [
        'amount' => (int) round($valor * 100), // BlackCat trabalha em centavos
        'currency' => 'BRL',
        'paymentMethod' => 'pix',
        'items' => [['title' => $descricao, 'quantity' => 1, 'tangible' => false]],
        'customer' => [
            'name' => $nome, 'email' => $email, 'phone' => $phone,
            'document' => ['number' => $cpf, 'type' => 'cpf'],
        ],
        'pix' => ['expiresInDays' => 1],
        'postbackUrl' => WEBHOOK_URL_BLACKCAT,
        'externalRef' => generateUniqueId(),
    ];
    $ch = curl_init('https://api.blackcatoficial.com/api/sales/create-sale');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-API-Key: ' . BLACKCAT_API_KEY],
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['success' => false, 'message' => 'Erro ao conectar (BlackCat): ' . $err];
    $r = json_decode($response, true);

    if (in_array($httpCode, [200, 201], true) && !empty($r['success']) && !empty($r['data']['transactionId'])) {
        $tx = $r['data'];
        $transactionId = $tx['transactionId'];
        $safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);
        $payment = [
            'payment_id' => generateUniqueId(), 'transaction_id' => $transactionId,
            'gateway' => 'blackcat', 'status' => 'pending',
            'nome' => $nome, 'email' => $email, 'cpf' => $cpf, 'phone' => $phone,
            'valor' => $valor, 'descricao' => $descricao,
            'qrcode' => $tx['paymentData']['copyPaste'] ?? null,
            'qrcode_base64' => $tx['paymentData']['qrCodeBase64'] ?? null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents(PENDING_DIR . '/' . $safeId . '.json', json_encode($payment, JSON_PRETTY_PRINT));
        return [
            'success' => true, 'transaction_id' => $transactionId,
            'qrcode' => $tx['paymentData']['copyPaste'] ?? null,
            'qrcode_base64' => $tx['paymentData']['qrCodeBase64'] ?? null,
            'amount' => $valor,
        ];
    }
    return ['success' => false, 'message' => $r['message'] ?? ($r['error'] ?? 'Erro ao gerar PIX (BlackCat)')];
}

function gerarPixMisticPay($nome, $email, $cpf, $phone, $valor, $descricao) {
    $paymentId = generateUniqueId();
    $postData = [
        'amount' => round($valor, 2), // MisticPay trabalha em reais no create
        'payerName' => $nome, 'payerDocument' => $cpf,
        'transactionId' => $paymentId, 'description' => $descricao,
        'projectWebhook' => WEBHOOK_URL_MISTICPAY,
    ];
    $ch = curl_init('https://api.misticpay.com/api/transactions/create');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'ci: ' . MISTICPAY_CLIENT_ID,
            'cs: ' . MISTICPAY_CLIENT_SECRET,
        ],
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['success' => false, 'message' => 'Erro ao conectar (MisticPay): ' . $err];
    $r = json_decode($response, true);

    if (in_array($httpCode, [200, 201], true) && !empty($r['data']['transactionId'])) {
        $tx = $r['data'];
        // transactionId aqui é o ID INTERNO da MisticPay (diferente do $paymentId
        // que enviamos) — é ele que volta no payload do webhook.
        $transactionId = (string) $tx['transactionId'];
        $safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);
        $payment = [
            'payment_id' => $paymentId, 'transaction_id' => $transactionId,
            'gateway' => 'misticpay', 'status' => 'pending',
            'nome' => $nome, 'email' => $email, 'cpf' => $cpf, 'phone' => $phone,
            'valor' => $valor, 'descricao' => $descricao,
            'qrcode' => $tx['copyPaste'] ?? null, 'qrcode_base64' => $tx['qrCodeBase64'] ?? null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents(PENDING_DIR . '/' . $safeId . '.json', json_encode($payment, JSON_PRETTY_PRINT));
        return [
            'success' => true, 'transaction_id' => $transactionId,
            'qrcode' => $tx['copyPaste'] ?? null, 'qrcode_base64' => $tx['qrCodeBase64'] ?? null,
            'amount' => $valor,
        ];
    }
    return ['success' => false, 'message' => $r['message'] ?? 'Erro ao gerar PIX (MisticPay)'];
}
PHP;
}

/* ═══════════════════════════════════════════════════════════════
   GERA check-payment.php
═══════════════════════════════════════════════════════════════ */
function buildCheckPaymentPhp(): string
{
    return <<<'PHP'
<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$txId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $_GET['transaction_id'] ?? $_GET['id'] ?? '');

if (!$txId) {
    echo json_encode(['success' => false, 'status' => 'invalid']);
    exit;
}

$paidFile    = PAID_DIR    . '/' . $txId . '.json';
$pendingFile = PENDING_DIR . '/' . $txId . '.json';

if (file_exists($paidFile)) {
    $pay = json_decode(file_get_contents($paidFile), true);
    echo json_encode([
        'success' => true, 'status' => 'paid',
        'paid_at' => $pay['paid_at'] ?? null, 'amount' => $pay['valor'] ?? null,
    ]);
} elseif (file_exists($pendingFile)) {
    echo json_encode(['success' => true, 'status' => 'pending']);
} else {
    echo json_encode(['success' => false, 'status' => 'not_found']);
}
PHP;
}

/* ═══════════════════════════════════════════════════════════════
   GERA webhook.php
═══════════════════════════════════════════════════════════════ */
function buildWebhookPhp(): string
{
    return <<<'PHP'
<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
ini_set('memory_limit', '128M');
ini_set('max_execution_time', '60');

function logWh(string $msg, $data = null): void {
    $line = '[' . date('Y-m-d H:i:s') . '] [' . ($_SERVER['REMOTE_ADDR'] ?? '?') . '] ' . $msg;
    if ($data !== null) $line .= ' | ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    @file_put_contents(__DIR__ . '/webhook.log', $line . "\n", FILE_APPEND);
}

logWh('===== WEBHOOK =====');
require_once __DIR__ . '/config.php';

$mailerAvailable = file_exists(__DIR__ . '/vendor/autoload.php');
if ($mailerAvailable) {
    require __DIR__ . '/vendor/autoload.php';
    // O import do namespace seria injetado aqui via compilador ou feito direto no script raiz
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?? [];
logWh('Payload recebido', $data);

$txId   = $data['transactionId']   ?? $data['transaction_id'] ?? $data['txid']
       ?? $data['charge_id']       ?? $data['id']             ?? '';
$status = strtoupper(
    $data['status'] ?? $data['charge_status'] ?? $data['payment_status'] ?? ''
);
$type   = $data['transactionType'] ?? $data['event'] ?? $data['type'] ?? '';

if (str_contains(strtolower($type), 'paid') || str_contains(strtolower($type), 'approved')) {
    $status = 'PAID';
}
if ($type === 'RECEIVEPIX' && $status !== 'PAID') {
    http_response_code(200); echo 'Received'; exit;
}

logWh('txId=' . $txId . ' status=' . $status);

if ($status !== 'PAID' || !$txId) {
    http_response_code(200);
    echo 'Received';
    exit;
}

$pendingFile = PENDING_DIR . '/' . $txId . '.json';
$paidFile    = PAID_DIR    . '/' . $txId . '.json';

if (!file_exists($pendingFile)) {
    logWh('Pendente não encontrado: ' . $txId);
    http_response_code(200);
    echo 'Not found';
    exit;
}

$pay              = json_decode(file_get_contents($pendingFile), true);
$pay['status']    = 'paid';
$pay['paid_at']   = date('Y-m-d H:i:s');
$pay['webhook']   = $data;

file_put_contents($paidFile, json_encode($pay, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
unlink($pendingFile);
logWh('Pago salvo: ' . $txId);

// Adicionar envio de e-mail opcionalmente como você já tinha...

http_response_code(200);
ob_end_clean();
echo 'OK';
PHP;
}

/* ═══════════════════════════════════════════════════════════════
   GERA webhook-nexypay.php
═══════════════════════════════════════════════════════════════ */
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

/* ═══════════════════════════════════════════════════════════════
   GERA webhook-blackcat.php
   BlackCat não documenta assinatura no payload — por isso, diferente
   do webhook-nexypay.php acima, este reconfirma o status direto na
   API (GET /sales/{id}/status) antes de marcar como pago.
═══════════════════════════════════════════════════════════════ */
function buildWebhookBlackcatPhp(): string
{
    return <<<'PHP'
<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/config.php';

function logWhB(string $msg, $data = null): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($data !== null) $line .= ' | ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    @file_put_contents(__DIR__ . '/webhook-blackcat.log', $line . "\n", FILE_APPEND);
}

function consultarStatusBlackCatWh(string $transactionId): array {
    $ch = curl_init('https://api.blackcatoficial.com/api/sales/' . rawurlencode($transactionId) . '/status');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['X-API-Key: ' . BLACKCAT_API_KEY],
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['success' => false, 'error' => $err];
    $data = json_decode($response, true);
    return is_array($data) ? $data : ['success' => false, 'raw' => $response];
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?? [];
logWhB('Payload recebido', $data);

$txId  = $data['transactionId'] ?? '';
$event = $data['event'] ?? ($_SERVER['HTTP_X_WEBHOOK_EVENT'] ?? '');

if ($event !== 'transaction.paid' || !$txId) {
    http_response_code(200);
    echo 'Received';
    exit;
}

$statusReal = consultarStatusBlackCatWh($txId);
if (empty($statusReal['success']) || ($statusReal['data']['status'] ?? '') !== 'PAID') {
    logWhB('Status nao confirmado como pago', $statusReal);
    http_response_code(200);
    echo 'Received';
    exit;
}

$safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $txId);
$pendingFile = PENDING_DIR . '/' . $safeId . '.json';
$paidFile    = PAID_DIR    . '/' . $safeId . '.json';

if (file_exists($paidFile)) {
    http_response_code(200);
    echo 'OK';
    exit;
}

if (!file_exists($pendingFile)) {
    logWhB('Pendente não encontrado: ' . $safeId);
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
logWhB('Pago: ' . $safeId);

http_response_code(200);
echo 'OK';
PHP;
}

/* ═══════════════════════════════════════════════════════════════
   GERA webhook-misticpay.php
   MisticPay também não documenta assinatura — reconfirma via
   POST /api/transactions/check antes de marcar como pago.
═══════════════════════════════════════════════════════════════ */
function buildWebhookMisticpayPhp(): string
{
    return <<<'PHP'
<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/config.php';

function logWhM(string $msg, $data = null): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($data !== null) $line .= ' | ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    @file_put_contents(__DIR__ . '/webhook-misticpay.log', $line . "\n", FILE_APPEND);
}

function consultarStatusMisticPayWh(string $transactionId): array {
    $ch = curl_init('https://api.misticpay.com/api/transactions/check');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['transactionId' => $transactionId]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'ci: ' . MISTICPAY_CLIENT_ID,
            'cs: ' . MISTICPAY_CLIENT_SECRET,
        ],
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['transaction' => null, 'error' => $err];
    $data = json_decode($response, true);
    return is_array($data) ? $data : ['transaction' => null, 'raw' => $response];
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?? [];
logWhM('Payload recebido', $data);

$txId = (string) ($data['transactionId'] ?? '');
$type = $data['transactionType'] ?? '';

if ($type !== 'DEPOSITO' || !$txId) {
    http_response_code(200);
    echo 'Received';
    exit;
}

$statusReal = consultarStatusMisticPayWh($txId);
if (($statusReal['transaction']['transactionState'] ?? '') !== 'COMPLETO') {
    logWhM('Status nao confirmado como pago', $statusReal);
    http_response_code(200);
    echo 'Received';
    exit;
}

$safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $txId);
$pendingFile = PENDING_DIR . '/' . $safeId . '.json';
$paidFile    = PAID_DIR    . '/' . $safeId . '.json';

if (file_exists($paidFile)) {
    http_response_code(200);
    echo 'OK';
    exit;
}

if (!file_exists($pendingFile)) {
    logWhM('Pendente não encontrado: ' . $safeId);
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
logWhM('Pago: ' . $safeId);

http_response_code(200);
echo 'OK';
PHP;
}

/* ═══════════════════════════════════════════════════════════════
   GERA .htaccess
═══════════════════════════════════════════════════════════════ */
function buildHtaccess(string $projectName): string
{
    return <<<HTACCESS
# ════════════════════════════════════════════════
# .htaccess — Editor de Produto PRO
# Projeto: {$projectName}
# ════════════════════════════════════════════════
Options -Indexes
<FilesMatch "(config\.php|\.log|\.json)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^payments/ - [F,L]
</IfModule>
<IfModule mod_headers.c>
    <FilesMatch "(api-pix|check-payment|webhook[\w-]*)\.php$">
        Header set Access-Control-Allow-Origin "*"
        Header set Access-Control-Allow-Methods "POST, GET, OPTIONS"
        Header set Access-Control-Allow-Headers "Content-Type, Authorization"
    </FilesMatch>
</IfModule>
HTACCESS;
}

/* ═══════════════════════════════════════════════════════════════
   GERA install.php
═══════════════════════════════════════════════════════════════ */
function buildInstallPhp(): string
{
    return <<<'PHP'
<?php
require_once __DIR__ . '/config.php';
$ok = true;
$msgs = [];
$dirs = [PAYMENTS_DIR, PENDING_DIR, PAID_DIR];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0750, true)) $msgs[] = "✅ Criado: $dir";
        else { $msgs[] = "❌ Falhou ao criar: $dir"; $ok = false; }
    } else $msgs[] = "✅ Já existe: $dir";
    $ha = $dir . '/.htaccess';
    if (!file_exists($ha)) {
        file_put_contents($ha, "Order Allow,Deny\nDeny from all\n");
        $msgs[] = "✅ .htaccess criado em: $dir";
    }
}
$testFile = PENDING_DIR . '/test_' . time() . '.tmp';
if (file_put_contents($testFile, 'ok') !== false) {
    unlink($testFile);
    $msgs[] = "✅ Escrita em PENDING_DIR: OK";
} else {
    $msgs[] = "❌ Sem permissão de escrita em: " . PENDING_DIR;
    $ok = false;
}
echo '<body>';
foreach ($msgs as $m) echo "<p>" . htmlspecialchars($m) . "</p>";
echo '</body>';
PHP;
}

/* ═══════════════════════════════════════════════════════════════
   GERA README.txt
═══════════════════════════════════════════════════════════════ */
function buildReadme(string $projectName, string $gwProvider): string
{
    $now = date('d/m/Y H:i');
    return <<<TXT
═══════════════════════════════════════════════════
  EDITOR DE PRODUTO PRO — RZ Tecnologia
  Projeto   : {$projectName}
  Gateway   : {$gwProvider}
  Gerado em : {$now}
═══════════════════════════════════════════════════
TXT;
}

/* ═══════════════════════════════════════════════════════════════
   PROCESSAMENTO DOS TEMPLATES HTML E MONTAGEM DO ZIP
═══════════════════════════════════════════════════════════════ */
try {
    $lpHtml = readTemplate('template_lp.html');
    $checkoutHtml = readTemplate('checkout.html');
    $cartHtml = readTemplate('cart.html');
    $storeHtml = readTemplate('store.html');
    $chatHtml = readTemplate('chat.html');
} catch (RuntimeException $e) {
    ob_end_clean();
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

$dataScript = buildDataScript($proj, true);
$lpProcessed = processHtml($lpHtml, $proj, $dataScript);
$coProcessed = processHtml($checkoutHtml, $proj, $dataScript);

if (!class_exists('ZipArchive')) {
    ob_end_clean();
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Extensão ZipArchive não está disponível neste servidor.']));
}

$zipTmp = tempnam(sys_get_temp_dir(), 'epro_') . '.zip';
$zip = new ZipArchive();

if ($zip->open($zipTmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    ob_end_clean();
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Não foi possível criar o arquivo ZIP.']));
}

/* ── HTML principal ── */
$zip->addFromString('index.html', $lpProcessed);
$zip->addFromString('checkout.html', $coProcessed);
$zip->addFromString('cart.html', $cartHtml);
$zip->addFromString('store.html', processHtml($storeHtml, $proj, buildDataScript($proj, true)));
$zip->addFromString('chat.html', processHtml($chatHtml, $proj, buildDataScript($proj, true)));

/* ── Backend financeiro ── */
$zip->addFromString('config.php', buildConfigPhp($gwProvider, $gwPublic, $gwSecret, $projectName));
$zip->addFromString('api-pix.php', buildApiPixPhp());
$zip->addFromString('check-payment.php', buildCheckPaymentPhp());
$zip->addFromString('webhook.php', buildWebhookPhp());
$zip->addFromString('webhook-nexypay.php', buildWebhookNexypayPhp());
$zip->addFromString('webhook-blackcat.php', buildWebhookBlackcatPhp());
$zip->addFromString('webhook-misticpay.php', buildWebhookMisticpayPhp());
$zip->addFromString('.htaccess', buildHtaccess($projectName));
$zip->addFromString('install.php', buildInstallPhp());
$zip->addFromString('README.txt', buildReadme($projectName, $gwProvider));

/* ── Placeholder de diretórios ── */
$zip->addEmptyDir('payments');
$zip->addEmptyDir('payments/pending');
$zip->addEmptyDir('payments/paid');
$zip->addFromString('payments/.htaccess', "Order Allow,Deny\nDeny from all\n");
$zip->addFromString('payments/pending/.htaccess', "Order Allow,Deny\nDeny from all\n");
$zip->addFromString('payments/paid/.htaccess', "Order Allow,Deny\nDeny from all\n");

/* ── Assets do admin (se existirem) ── */
$assetDirs = [
    'assets/admin.css',
    'assets/admin.js',
];
foreach ($assetDirs as $assetPath) {
    $full = __DIR__ . '/' . $assetPath;
    if (file_exists($full)) {
        $zip->addFile($full, $assetPath);
    }
}

$zip->close();

/* ═══════════════════════════════════════════════════════════════
   DOWNLOAD
═══════════════════════════════════════════════════════════════ */
ob_end_clean();

$filename = 'epro-' . $projectName . '-' . date('Ymd-His') . '.zip';
$filesize = filesize($zipTmp);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($zipTmp);
unlink($zipTmp);
exit;