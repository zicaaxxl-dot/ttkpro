<?php
/**
 * email-helper.php
 * Função compartilhada de envio de email de confirmação de pagamento.
 * Usada por webhook.php (Ecompag) e webhook-nexypay.php (QuantiumPay).
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

function enviarEmailConfirmacao($paymentData)
{
    $to = $paymentData['email'];
    $nome = $paymentData['nome'];
    $valor = number_format($paymentData['valor'], 2, ',', '.');
    $plano = $paymentData['plano'];
    $transactionId = $paymentData['transaction_id'];

    $planos = [
        'teste' => 'Teste (24h)',
        'semanal' => 'Semanal',
        'mensal' => 'Mensal',
        'monthly' => 'Mensal',
        'trimestral' => 'Trimestral',
        'quarterly' => 'Trimestral',
        'anual' => 'Anual',
        'yearly' => 'Anual',
        'vitalicio' => 'Vitalicio'
    ];
    $planoNome = $planos[$plano] ?? 'Mensal';

    $accessLink = ACCESS_LINK;
    $subject = EMAIL_SUBJECT;

    $emailHtml = '<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagamento Confirmado</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f5f5f5">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f5f5f5;padding:20px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1)">
          <tr>
            <td style="background:linear-gradient(135deg, #ff7643, #ff6530);color:white;padding:40px 20px;text-align:center">
              <h1 style="margin:0;font-size:28px;font-weight:bold">Pagamento Confirmado!</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:40px 30px">
              <div style="text-align:center;margin-bottom:30px">
                <svg width="80" height="80" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="11" fill="#10b981"/>
                  <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                </svg>
              </div>
              <h2 style="color:#1a1a1a;font-size:24px;margin-bottom:20px;text-align:center">Ola, ' . htmlspecialchars($nome) . '!</h2>
              <p style="text-align:center;color:#666;font-size:16px;margin-bottom:30px;line-height:1.6">
                Seu pagamento foi confirmado com sucesso!<br>
                Agora voce tem acesso total ao conteudo exclusivo.
              </p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8f9fa;border-left:4px solid #ff7643;padding:20px;margin:20px 0;border-radius:5px">
                <tr>
                  <td>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td style="padding:8px 0;border-bottom:1px solid #e0e0e0">
                          <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                              <td style="color:#666;font-weight:500;font-size:14px">Plano:</td>
                              <td align="right" style="color:#1a1a1a;font-weight:600;font-size:14px">Assinatura ' . htmlspecialchars($planoNome) . '</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:8px 0;border-bottom:1px solid #e0e0e0">
                          <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                              <td style="color:#666;font-weight:500;font-size:14px">Valor Pago:</td>
                              <td align="right" style="color:#1a1a1a;font-weight:600;font-size:14px">R$ ' . htmlspecialchars($valor) . '</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:8px 0;border-bottom:1px solid #e0e0e0">
                          <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                              <td style="color:#666;font-weight:500;font-size:14px">ID da Transacao:</td>
                              <td align="right" style="color:#1a1a1a;font-weight:600;font-size:14px">' . htmlspecialchars($transactionId) . '</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:8px 0">
                          <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                              <td style="color:#666;font-weight:500;font-size:14px">Data:</td>
                              <td align="right" style="color:#1a1a1a;font-weight:600;font-size:14px">' . date('d/m/Y H:i') . '</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td align="center" style="padding:30px 0">
                    <a href="' . htmlspecialchars($accessLink) . '" style="display:inline-block;background:linear-gradient(135deg, #ff7643, #ff6530);color:white;text-decoration:none;padding:18px 30px;border-radius:8px;font-weight:600;font-size:16px">Acessar Conteudo Agora</a>
                  </td>
                </tr>
              </table>
              <p style="text-align:center;color:#999;font-size:14px;margin-top:30px">
                Se voce tiver alguma duvida, entre em contato conosco.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background:#f8f9fa;padding:20px;text-align:center;color:#666;font-size:12px;line-height:1.6">
              <p style="margin:0 0 10px 0">
                2026 Privacy - Eduarda. Todos os direitos reservados.<br>
                Este e um email automatico, por favor nao responda.
              </p>
              <p style="margin:0">
                <a href="' . htmlspecialchars(SITE_URL) . '" style="color:#ff7643;text-decoration:none">Acessar Site</a> | 
                <a href="' . htmlspecialchars(SITE_URL) . '/suporte" style="color:#ff7643;text-decoration:none">Suporte</a>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

    $emailText = "Pagamento Confirmado!\n\n";
    $emailText .= "Ola, {$nome}!\n\n";
    $emailText .= "Seu pagamento foi confirmado com sucesso!\n";
    $emailText .= "Agora voce tem acesso total ao conteudo exclusivo.\n\n";
    $emailText .= "Detalhes do Pagamento:\n";
    $emailText .= "- Plano: Assinatura {$planoNome}\n";
    $emailText .= "- Valor Pago: R$ {$valor}\n";
    $emailText .= "- ID da Transacao: {$transactionId}\n";
    $emailText .= "- Data: " . date('d/m/Y H:i') . "\n\n";
    $emailText .= "Acesse agora: {$accessLink}\n\n";
    $emailText .= "Se voce tiver alguma duvida, entre em contato conosco.\n\n";
    $emailText .= "2026 Privacy - Eduarda. Todos os direitos reservados.\n";
    $emailText .= "Este e um email automatico, por favor nao responda.";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->Timeout = 30;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($to, $nome);

        $mail->Priority = 1;
        $mail->addCustomHeader('X-Priority', '1');
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $emailHtml;
        $mail->AltBody = $emailText;
        $mail->SMTPDebug = 0;

        $mail->send();

        file_put_contents(
            __DIR__ . '/email.log',
            date('Y-m-d H:i:s') . " - Email para {$to}: ENVIADO COM SUCESSO - Plano: {$planoNome} - Valor: R$ {$valor}" . PHP_EOL,
            FILE_APPEND
        );

        return true;

    } catch (Exception $e) {
        file_put_contents(
            __DIR__ . '/email.log',
            date('Y-m-d H:i:s') . " - Email para {$to}: FALHOU - Erro: {$mail->ErrorInfo} - Exception: {$e->getMessage()}" . PHP_EOL,
            FILE_APPEND
        );
        return false;
    }
}