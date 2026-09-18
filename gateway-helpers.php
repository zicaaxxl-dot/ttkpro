<?php
/**
 * gateway-helpers.php
 * ------------------------------------------------------------
 * Funções de reconfirmação de status, compartilhadas entre
 * api-pix.php e os arquivos webhook-blackcat.php / webhook-misticpay.php.
 *
 * Por que isso existe: nem BlackCat nem MisticPay documentam
 * assinatura/HMAC no payload do webhook. Então, assim como já é
 * feito com a QuantiumPay em webhook-nexypay.php, nunca confiamos
 * cegamente no status que vem no POST do webhook — sempre
 * reconfirmamos direto na API antes de liberar o pagamento.
 *
 * Requer que config.php já tenha sido carregado antes
 * (usa BLACKCAT_API_KEY, MISTICPAY_CLIENT_ID, MISTICPAY_CLIENT_SECRET).
 */

/**
 * Consulta o status real de uma venda direto na API da BlackCat.
 * GET /sales/{transactionId}/status
 */
function consultarStatusBlackCat($transactionId)
{
    $ch = curl_init('https://api.blackcatoficial.com/api/sales/' . rawurlencode($transactionId) . '/status');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . BLACKCAT_API_KEY,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'error' => $curlError];
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : ['success' => false, 'raw' => $response];
}

/**
 * Consulta o status real de uma transação direto na API da MisticPay.
 * POST /api/transactions/check
 */
function consultarStatusMisticPay($transactionId)
{
    $ch = curl_init('https://api.misticpay.com/api/transactions/check');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['transactionId' => $transactionId]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'ci: ' . MISTICPAY_CLIENT_ID,
            'cs: ' . MISTICPAY_CLIENT_SECRET,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['transaction' => null, 'error' => $curlError];
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : ['transaction' => null, 'raw' => $response];
}

/**
 * Consulta transacao Pixzy (UUID ou id).
 * GET https://app.pixzypay.com/api/transactions/{id}
 */
function consultarStatusPixzy($transactionId)
{
    $ch = curl_init('https://app.pixzypay.com/api/transactions/' . rawurlencode($transactionId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . PIXZY_API_TOKEN,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'error' => $curlError];
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : ['success' => false, 'raw' => $response];
}