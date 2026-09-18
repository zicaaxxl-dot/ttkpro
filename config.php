<?php
date_default_timezone_set('America/Sao_Paulo');
/**
 * Configurações Dinâmicas do Sistema de Pagamento PIX
 * Ecompag, QuantiumPay, BlackCat e MisticPay API Integration (Vinculado ao Admin)
 *
 * Lê o gateway ativo direto da tabela gateway_settings (mesma tabela que o
 * admin.php grava via save_gateway.php). Uma única fonte de verdade.
 *
 * Mapeamento de credenciais por provider:
 *   - ecompag    -> public_key = Client ID   | secret_key = Client Secret
 *   - nexypay    -> secret_key = API Key     (cai pro public_key se secret vier vazio)
 *   - blackcat   -> secret_key = X-API-Key   (cai pro public_key se secret vier vazio)
 *   - misticpay  -> public_key = Client ID (ci) | secret_key = Client Secret (cs)
 */

require_once __DIR__ . '/functions.php';

// ============================================================
// 1. LEITURA DO GATEWAY ATIVO (BANCO DE DADOS)
// ============================================================
$gatewaySettings = getGatewaySettings();
$gateway_provider = $gatewaySettings['provider'];
$gateway_public = $gatewaySettings['public_key'];
$gateway_secret = $gatewaySettings['secret_key'];

// ============================================================
// 2. APLICAÇÃO DAS CREDENCIAIS E GATEWAY ATIVO
// ============================================================
define('ACTIVE_GATEWAY', $gateway_provider);

// Credenciais Ecompag (Client ID = Public | Client Secret = Secret)
define('ECOMPAG_CLIENT_ID', $gateway_public);
define('ECOMPAG_CLIENT_SECRET', $gateway_secret);

// Credenciais QuantiumPay (a API Key geralmente vai no campo Secret;
// cai pro Public só se o Secret vier vazio)
define('NEXYPAY_API_KEY', !empty($gateway_secret) ? $gateway_secret : $gateway_public);

// Credenciais BlackCat (X-API-Key única; mesmo padrão de fallback da QuantiumPay)
define('BLACKCAT_API_KEY', !empty($gateway_secret) ? $gateway_secret : $gateway_public);

// Credenciais MisticPay (Client ID + Client Secret, headers "ci" e "cs")
define('MISTICPAY_CLIENT_ID', $gateway_public);
define('MISTICPAY_CLIENT_SECRET', $gateway_secret);

// ============================================================
// 3. CONFIGURAÇÕES DE URL E DIRETÓRIOS (mantidos iguais)
// ============================================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', rtrim($protocol . '://' . $host, '/'));
define('WEBHOOK_URL_ECOMPAG', SITE_URL . '/webhook.php');
define('WEBHOOK_URL_NEXYPAY', SITE_URL . '/webhook-nexypay.php');
define('WEBHOOK_URL_BLACKCAT', SITE_URL . '/webhook-blackcat.php');
define('WEBHOOK_URL_MISTICPAY', SITE_URL . '/webhook-misticpay.php');
define('WEBHOOK_URL', WEBHOOK_URL_ECOMPAG); // mantido por compatibilidade

// Diretórios de armazenamento (payments/* pode ser removido depois que
// migrar de vez pra tabela `payments`, mas deixei por segurança/transição)
define('PAYMENTS_DIR', __DIR__ . '/payments');
define('PENDING_DIR', PAYMENTS_DIR . '/pending');
define('PAID_DIR', PAYMENTS_DIR . '/paid');

// Configurações de Email
define('EMAIL_FROM', 'seuemailaqui@site.com');
define('EMAIL_FROM_NAME', 'TikTok Shop');
define('EMAIL_SUBJECT', 'Pagamento Confirmado - Acesso Liberado!');

// Configurações SMTP
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seuemailaqui@site.com');
define('SMTP_PASSWORD', 'suasenhaaqui');

// Link de Acesso ao Conteúdo
define('ACCESS_LINK', 'https://t.me/link');

// Salt para validação do token de acesso
define('ACCESS_TOKEN_SALT', 'Pr1v@cyEdu4rd@2026#xK9');

// Criar diretórios se não existirem
if (!file_exists(PAYMENTS_DIR))
    mkdir(PAYMENTS_DIR, 0755, true);
if (!file_exists(PENDING_DIR))
    mkdir(PENDING_DIR, 0755, true);
if (!file_exists(PAID_DIR))
    mkdir(PAID_DIR, 0755, true);

// Função para gerar ID único
function generateUniqueId()
{
    return uniqid('payment_', true) . '_' . time();
}

// Função para sanitizar dados
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Função para validar CPF
function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11)
        return false;
    if (preg_match('/(\d)\1{10}/', $cpf))
        return false;
    return true;
}

// Função para validar Email
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}