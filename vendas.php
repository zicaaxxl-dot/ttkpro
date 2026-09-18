<?php
/**
 * Gerenciamento de vendas (pagamentos) geradas no checkout.
 * Fonte: tabela payments — atualizada pelo webhook quando o PIX é pago.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/functions.php';
requireLogin();

ensureProjectTablesSchema();

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_all') {
    try {
        db()->exec('DELETE FROM payments');
        $flash = 'Lista de vendas limpa. (No Render free o banco tambem some no reset do container.)';
    } catch (Throwable $e) {
        $flash = 'Nao foi possivel limpar: ' . $e->getMessage();
    }
}

$filter = $_GET['status'] ?? 'all';
$payments = listPayments(300);
if ($filter !== 'all') {
    $payments = array_values(array_filter($payments, static function ($p) use ($filter) {
        return ($p['status'] ?? '') === $filter;
    }));
}

$counts = ['all' => 0, 'pending' => 0, 'paid' => 0, 'cancelled' => 0, 'expired' => 0];
foreach (listPayments(500) as $p) {
    $counts['all']++;
    $st = $p['status'] ?? 'pending';
    if (isset($counts[$st])) {
        $counts[$st]++;
    }
}

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function moneyBr($v): string
{
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas - ttkpro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Inter, system-ui, sans-serif; color: #111827; }
        body { background: #f3f4f6; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .logo { font-weight: 800; font-size: 18px; }
        .logo span { color: #fe2c55; }
        .nav a { margin-left: 10px; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 12px; border-radius: 6px; background: #111827; color: #fff; }
        .nav a.ghost { background: #fff; color: #111827; border: 1px solid #e5e7eb; }
        .wrap { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px; }
        h1 { font-size: 20px; margin-bottom: 6px; }
        .hint { font-size: 12px; color: #6b7280; margin-bottom: 16px; line-height: 1.5; }
        .filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .filters a { font-size: 12px; font-weight: 600; text-decoration: none; padding: 7px 12px; border-radius: 999px; border: 1px solid #e5e7eb; color: #374151; background: #fff; }
        .filters a.active { background: #111827; color: #fff; border-color: #111827; }
        .flash { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 10px 12px; border-radius: 8px; font-size: 13px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
        .badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; }
        .paid { background: #d1fae5; color: #065f46; }
        .pending { background: #fef3c7; color: #92400e; }
        .cancelled, .expired { background: #fee2e2; color: #991b1b; }
        .mono { font-family: ui-monospace, monospace; font-size: 11px; word-break: break-all; }
        .actions { margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .btn-danger { background: #dc2626; color: #fff; border: 0; padding: 9px 14px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; }
        .empty { padding: 28px; text-align: center; color: #6b7280; font-size: 14px; }
        @media (max-width: 720px) {
            .hide-sm { display: none; }
        }
    </style>
</head>
<body>
<header class="header">
    <div class="logo">TTK<span>PRO</span></div>
    <div class="nav">
        <a class="ghost" href="admin.php">Editor</a>
        <a href="logout.php">Sair</a>
    </div>
</header>

<div class="wrap">
    <div class="card">
        <h1>Vendas geradas</h1>
        <p class="hint">
            Toda venda paga e aprovada pelo <strong>webhook</strong> da Pixzy (status no banco).
            No Render free o disco/banco pode zerar no sleep/reset — use esta tela enquanto o app estiver no ar.
            O cliente nao fica preso: o check-payment le o banco assim que o webhook marca <strong>paid</strong>.
        </p>

        <?php if ($flash !== ''): ?>
            <div class="flash"><?= h($flash) ?></div>
        <?php endif; ?>

        <div class="filters">
            <a class="<?= $filter === 'all' ? 'active' : '' ?>" href="?status=all">Todas (<?= (int) $counts['all'] ?>)</a>
            <a class="<?= $filter === 'paid' ? 'active' : '' ?>" href="?status=paid">Pagas (<?= (int) $counts['paid'] ?>)</a>
            <a class="<?= $filter === 'pending' ? 'active' : '' ?>" href="?status=pending">Pendentes (<?= (int) $counts['pending'] ?>)</a>
        </div>

        <?php if (!$payments): ?>
            <div class="empty">Nenhuma venda registrada ainda.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Cliente</th>
                            <th class="hide-sm">ID / Gateway</th>
                            <th>Criado</th>
                            <th>Pago em</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $p): ?>
                        <?php $st = $p['status'] ?? 'pending'; ?>
                        <tr>
                            <td><span class="badge <?= h($st) ?>"><?= h(strtoupper($st)) ?></span></td>
                            <td><?= moneyBr($p['amount'] ?? 0) ?></td>
                            <td>
                                <div><?= h($p['customer_name'] ?: '—') ?></div>
                                <div style="font-size:11px;color:#6b7280"><?= h($p['customer_email'] ?: '') ?></div>
                                <div style="font-size:11px;color:#6b7280"><?= h($p['customer_cpf'] ?: '') ?></div>
                            </td>
                            <td class="hide-sm">
                                <div class="mono"><?= h($p['payment_id'] ?? '') ?></div>
                                <div style="font-size:11px;color:#6b7280;margin-top:4px"><?= h($p['gateway'] ?? '') ?></div>
                            </td>
                            <td><?= h($p['created_at'] ?? '') ?></td>
                            <td><?= h($p['paid_at'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="actions">
            <form method="post" onsubmit="return confirm('Apagar TODAS as vendas do banco? Isso nao cancela cobrancas na Pixzy.');">
                <input type="hidden" name="action" value="clear_all">
                <button type="submit" class="btn-danger">Limpar lista de vendas</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
