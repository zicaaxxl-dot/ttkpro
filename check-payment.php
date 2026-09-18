<?php
/**
 * Verificar status de pagamento
 * Prioridade: banco (webhook) → arquivos paid/pending
 */

header('Content-Type: application/json');
require_once 'config.php';

$transactionId = $_GET['transaction_id'] ?? '';
$transactionId = preg_replace('/[^a-zA-Z0-9_.\-]/', '', (string) $transactionId);

if ($transactionId === '') {
    echo json_encode([
        'success' => false,
        'status' => 'invalid',
        'message' => 'ID de transação inválido'
    ]);
    exit;
}

function respondPaid(string $transactionId, $amount = null, $paidAt = null): void
{
    $tok = substr(hash('sha256', $transactionId . ACCESS_TOKEN_SALT), 0, 32);
    echo json_encode([
        'success' => true,
        'status' => 'paid',
        'tok' => $tok,
        'message' => 'Pagamento confirmado',
        'data' => [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'paid_at' => $paidAt
        ]
    ]);
}

// 1) Banco primeiro — webhook grava aqui mesmo se o disco pending sumir
$db = getPaymentById($transactionId);
if ($db) {
    if (($db['status'] ?? '') === 'paid') {
        respondPaid(
            $transactionId,
            $db['amount'] ?? null,
            $db['paid_at'] ?? null
        );
        exit;
    }
    if (($db['status'] ?? '') === 'pending') {
        echo json_encode([
            'success' => true,
            'status' => 'pending',
            'message' => 'Aguardando pagamento'
        ]);
        exit;
    }
}

// 2) Arquivos (compatibilidade / ZIP exportado)
$paidFile = PAID_DIR . '/' . $transactionId . '.json';
$pendingFile = PENDING_DIR . '/' . $transactionId . '.json';

if (file_exists($paidFile)) {
    $paymentData = json_decode(file_get_contents($paidFile), true);
    respondPaid(
        $transactionId,
        $paymentData['valor'] ?? null,
        $paymentData['paid_at'] ?? null
    );
    exit;
}

if (file_exists($pendingFile)) {
    echo json_encode([
        'success' => true,
        'status' => 'pending',
        'message' => 'Aguardando pagamento'
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'status' => 'not_found',
    'message' => 'Pagamento não encontrado'
]);
