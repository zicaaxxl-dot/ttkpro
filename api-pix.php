<?php
/**
 * API PIX - Gerar cobrança (QuantiumPay, Ecompag, BlackCat ou MisticPay)
 */

header('Content-Type: application/json');
require_once 'config.php';
require_once 'gateway-helpers.php'; // consultarStatusBlackCat() / consultarStatusMisticPay()

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Dados inválidos'
    ]);
    exit;
}

$nome = sanitize($data['name'] ?? '');
$email = sanitize($data['email'] ?? '');
$cpf = preg_replace('/[^0-9]/', '', $data['cpf'] ?? '');
$phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
$valor = floatval($data['amount'] ?? 0);
$plano = sanitize($data['plan'] ?? 'monthly');

$errors = [];

if (empty($nome) || strlen($nome) < 3) {
    $errors[] = 'Nome inválido';
}
if (!validarEmail($email)) {
    $errors[] = 'Email inválido';
}
if (!validarCPF($cpf)) {
    $errors[] = 'CPF inválido';
}
if ($valor <= 0) {
    $errors[] = 'Valor inválido';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro de validação',
        'errors' => $errors
    ]);
    exit;
}

$descricoes = [
    'monthly' => 'Assinatura Mensal - Privacy Eduarda',
    'quarterly' => 'Assinatura Trimestral - Privacy Eduarda',
    'yearly' => 'Assinatura Anual - Privacy Eduarda'
];
$descricao = $descricoes[$plano] ?? 'Assinatura Privacy - Eduarda';

switch (ACTIVE_GATEWAY) {
    case 'nexypay':
        $result = gerarPixQuantiumPay($nome, $email, $cpf, $phone, $valor, $descricao, $plano);
        break;
    case 'blackcat':
        $result = gerarPixBlackCat($nome, $email, $cpf, $phone, $valor, $descricao, $plano);
        break;
    case 'misticpay':
        $result = gerarPixMisticPay($nome, $email, $cpf, $phone, $valor, $descricao, $plano);
        break;
    case 'ecompag':
    default:
        $result = gerarPixEcompag($nome, $email, $cpf, $phone, $valor, $descricao, $plano);
        break;
}

echo json_encode($result);
exit;

/* ============================================================
   NEXYPAY
   ============================================================ */
function gerarPixQuantiumPay($nome, $email, $cpf, $phone, $valor, $descricao, $plano)
{
    $postData = [
        'api-key' => NEXYPAY_API_KEY,
        'amount' => round($valor, 2),
        'method' => 'pix',
        'client' => [
            'name' => $nome,
            'document' => $cpf,
            'email' => $email,
            'telefone' => $phone,
        ],
        'notification_url' => WEBHOOK_URL_NEXYPAY,
    ];

    $ch = curl_init('https://app.quantiumpay.com/api/v1/gateway/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    @file_put_contents(
        __DIR__ . '/api-pix.log',
        date('Y-m-d H:i:s') . " - [QuantiumPay] HTTP $httpCode - Response: $response" . PHP_EOL,
        FILE_APPEND
    );

    if ($curlError) {
        return ['success' => false, 'message' => 'Erro ao conectar com o gateway de pagamento (QuantiumPay)'];
    }

    $apiResponse = json_decode($response, true);

    if ($httpCode === 200 && isset($apiResponse['status']) && $apiResponse['status'] === 'success' && !empty($apiResponse['idTransaction'])) {

        $paymentId = generateUniqueId();
        $transactionId = $apiResponse['idTransaction'];
        $safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);

        $paymentData = [
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'gateway' => 'nexypay',
            'status' => 'pending',
            'nome' => $nome,
            'email' => $email,
            'cpf' => $cpf,
            'phone' => $phone,
            'valor' => $valor,
            'plano' => $plano,
            'descricao' => $descricao,
            'qrcode' => $apiResponse['paymentCode'],
            'qrcode_base64' => $apiResponse['paymentCodeBase64'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents(PENDING_DIR . '/' . $safeId . '.json', json_encode($paymentData, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'message' => 'QR Code gerado com sucesso',
            'transaction_id' => $transactionId,
            'qrcode' => $apiResponse['paymentCode'],
            'qrcode_base64' => $apiResponse['paymentCodeBase64'] ?? null,
            'amount' => $valor,
            'reference_code' => $transactionId,
        ];
    }

    return [
        'success' => false,
        'message' => $apiResponse['message'] ?? ($apiResponse['error'] ?? 'Erro ao gerar QR Code PIX (QuantiumPay)'),
        'details' => $apiResponse,
    ];
}

/* ============================================================
   ECOMPAG (mantido — usado se ACTIVE_GATEWAY = 'ecompag')
   ============================================================ */
function gerarPixEcompag($nome, $email, $cpf, $phone, $valor, $descricao, $plano)
{
    $postData = [
        'client_id' => ECOMPAG_CLIENT_ID,
        'client_secret' => ECOMPAG_CLIENT_SECRET,
        'nome' => $nome,
        'cpf' => $cpf,
        'valor' => $valor,
        'descricao' => $descricao,
        'urlnoty' => WEBHOOK_URL_ECOMPAG,
    ];

    $ch = curl_init('https://api.ecompag.com/v2/pix/qrcode.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    @file_put_contents(
        __DIR__ . '/api-pix.log',
        date('Y-m-d H:i:s') . " - [Ecompag] Response: $response" . PHP_EOL,
        FILE_APPEND
    );

    if ($curlError) {
        return ['success' => false, 'message' => 'Erro ao conectar com o gateway de pagamento'];
    }

    $apiResponse = json_decode($response, true);

    if ($httpCode === 200 && isset($apiResponse['qrcode'])) {
        $paymentId = generateUniqueId();
        $transactionId = $apiResponse['transactionId'];

        $paymentData = [
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'gateway' => 'ecompag',
            'status' => 'pending',
            'nome' => $nome,
            'email' => $email,
            'cpf' => $cpf,
            'phone' => $phone,
            'valor' => $valor,
            'plano' => $plano,
            'descricao' => $descricao,
            'qrcode' => $apiResponse['qrcode'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents(PENDING_DIR . '/' . $transactionId . '.json', json_encode($paymentData, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'message' => 'QR Code gerado com sucesso',
            'transaction_id' => $transactionId,
            'qrcode' => $apiResponse['qrcode'],
            'amount' => $valor,
            'reference_code' => $apiResponse['reference_code'] ?? $transactionId,
        ];
    }

    return [
        'success' => false,
        'message' => $apiResponse['message'] ?? 'Erro ao gerar QR Code PIX',
        'details' => $apiResponse,
    ];
}

/* ============================================================
   BLACKCAT
   Docs: https://docs.blackcatoficial.com
   Auth: header X-API-Key
   Valores em CENTAVOS
   ============================================================ */
function gerarPixBlackCat($nome, $email, $cpf, $phone, $valor, $descricao, $plano)
{
    $paymentId = generateUniqueId();

    $postData = [
        'amount' => (int) round($valor * 100), // centavos
        'currency' => 'BRL',
        'paymentMethod' => 'pix',
        'items' => [
            [
                'title' => $descricao,
                'quantity' => 1,
                'tangible' => false,
            ],
        ],
        'customer' => [
            'name' => $nome,
            'email' => $email,
            'phone' => $phone,
            'document' => [
                'number' => $cpf,
                'type' => 'cpf',
            ],
        ],
        'pix' => [
            'expiresInDays' => 1,
        ],
        'postbackUrl' => WEBHOOK_URL_BLACKCAT,
        'metadata' => $plano,
        'externalRef' => $paymentId,
    ];

    $ch = curl_init('https://api.blackcatoficial.com/api/sales/create-sale');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . BLACKCAT_API_KEY,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    @file_put_contents(
        __DIR__ . '/api-pix.log',
        date('Y-m-d H:i:s') . " - [BlackCat] HTTP $httpCode - Response: $response" . PHP_EOL,
        FILE_APPEND
    );

    if ($curlError) {
        return ['success' => false, 'message' => 'Erro ao conectar com o gateway de pagamento (BlackCat)'];
    }

    $apiResponse = json_decode($response, true);

    if (
        in_array($httpCode, [200, 201], true)
        && !empty($apiResponse['success'])
        && !empty($apiResponse['data']['transactionId'])
    ) {
        $tx = $apiResponse['data'];
        $transactionId = $tx['transactionId'];
        $safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);

        $paymentData = [
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'gateway' => 'blackcat',
            'status' => 'pending',
            'nome' => $nome,
            'email' => $email,
            'cpf' => $cpf,
            'phone' => $phone,
            'valor' => $valor,
            'plano' => $plano,
            'descricao' => $descricao,
            'qrcode' => $tx['paymentData']['copyPaste'] ?? null,
            'qrcode_base64' => $tx['paymentData']['qrCodeBase64'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents(PENDING_DIR . '/' . $safeId . '.json', json_encode($paymentData, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'message' => 'QR Code gerado com sucesso',
            'transaction_id' => $transactionId,
            'qrcode' => $tx['paymentData']['copyPaste'] ?? null,
            'qrcode_base64' => $tx['paymentData']['qrCodeBase64'] ?? null,
            'amount' => $valor,
            'reference_code' => $transactionId,
        ];
    }

    return [
        'success' => false,
        'message' => $apiResponse['message'] ?? ($apiResponse['error'] ?? 'Erro ao gerar QR Code PIX (BlackCat)'),
        'details' => $apiResponse,
    ];
}

/* ============================================================
   MISTICPAY
   Docs: https://docs.misticpay.com
   Auth: headers "ci" (client id) e "cs" (client secret)
   Valores no CREATE em REAIS (decimal); no WEBHOOK vêm em CENTAVOS
   ============================================================ */
function gerarPixMisticPay($nome, $email, $cpf, $phone, $valor, $descricao, $plano)
{
    $paymentId = generateUniqueId();

    $postData = [
        'amount' => round($valor, 2), // reais, ex: 5 = R$ 5,00
        'payerName' => $nome,
        'payerDocument' => $cpf,
        'transactionId' => $paymentId, // nosso identificador para correlação
        'description' => $descricao,
        'projectWebhook' => WEBHOOK_URL_MISTICPAY,
    ];

    $ch = curl_init('https://api.misticpay.com/api/transactions/create');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'ci: ' . MISTICPAY_CLIENT_ID,
            'cs: ' . MISTICPAY_CLIENT_SECRET,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    @file_put_contents(
        __DIR__ . '/api-pix.log',
        date('Y-m-d H:i:s') . " - [MisticPay] HTTP $httpCode - Response: $response" . PHP_EOL,
        FILE_APPEND
    );

    if ($curlError) {
        return ['success' => false, 'message' => 'Erro ao conectar com o gateway de pagamento (MisticPay)'];
    }

    $apiResponse = json_decode($response, true);

    if (
        in_array($httpCode, [200, 201], true)
        && !empty($apiResponse['data']['transactionId'])
    ) {
        $tx = $apiResponse['data'];
        // IMPORTANTE: o transactionId retornado aqui é o ID INTERNO da MisticPay
        // (diferente do $paymentId que enviamos). É esse ID interno que volta
        // no payload do webhook — por isso salvamos o pending com ele.
        $transactionId = (string) $tx['transactionId'];
        $safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $transactionId);

        $paymentData = [
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'gateway' => 'misticpay',
            'status' => 'pending',
            'nome' => $nome,
            'email' => $email,
            'cpf' => $cpf,
            'phone' => $phone,
            'valor' => $valor,
            'plano' => $plano,
            'descricao' => $descricao,
            'qrcode' => $tx['copyPaste'] ?? null,
            'qrcode_base64' => $tx['qrCodeBase64'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents(PENDING_DIR . '/' . $safeId . '.json', json_encode($paymentData, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'message' => 'QR Code gerado com sucesso',
            'transaction_id' => $transactionId,
            'qrcode' => $tx['copyPaste'] ?? null,
            'qrcode_base64' => $tx['qrCodeBase64'] ?? null,
            'amount' => $valor,
            'reference_code' => $transactionId,
        ];
    }

    return [
        'success' => false,
        'message' => $apiResponse['message'] ?? 'Erro ao gerar QR Code PIX (MisticPay)',
        'details' => $apiResponse,
    ];
}