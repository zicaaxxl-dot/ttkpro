<?php
/**
 * Produtos salvos no servidor — listar, editar, abrir pagina publica, excluir.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/functions.php';
requireLogin();

ensureProjectTablesSchema();
$projects = listProjects();

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
    <title>Produtos - ttkpro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Inter, system-ui, sans-serif; color: #111827; }
        body { background: #f3f4f6; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .logo { font-weight: 800; font-size: 18px; }
        .logo span { color: #fe2c55; }
        .nav a { margin-left: 8px; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 12px; border-radius: 6px; background: #111827; color: #fff; display: inline-block; }
        .nav a.ghost { background: #fff; color: #111827; border: 1px solid #e5e7eb; }
        .wrap { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px; }
        h1 { font-size: 20px; margin-bottom: 6px; }
        .hint { font-size: 12px; color: #6b7280; margin-bottom: 16px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 12px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
        .badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; }
        .on { background: #d1fae5; color: #065f46; }
        .off { background: #f3f4f6; color: #6b7280; }
        .actions { display: flex; flex-wrap: wrap; gap: 6px; }
        .btn { border: 0; border-radius: 6px; padding: 7px 10px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-edit { background: #111827; color: #fff; }
        .btn-view { background: #fff; color: #111827; border: 1px solid #e5e7eb; }
        .btn-del { background: #fee2e2; color: #991b1b; }
        .empty { padding: 36px 16px; text-align: center; color: #6b7280; }
        .top-actions { margin-bottom: 14px; }
        .mono { font-family: ui-monospace, monospace; font-size: 11px; color: #6b7280; }
        @media (max-width: 720px) { .hide-sm { display: none; } }
    </style>
</head>
<body>
<header class="header">
    <div class="logo">TTK<span>PRO</span></div>
    <div class="nav">
        <a class="ghost" href="admin.php">Editor</a>
        <a class="ghost" href="vendas.php">Vendas</a>
        <a href="logout.php">Sair</a>
    </div>
</header>

<div class="wrap">
    <div class="card">
        <h1>Produtos no servidor</h1>
        <p class="hint">
            Estes produtos ficam no banco persistente do Render. Edite, publique de novo ou abra a pagina publica.
            Depois de criar/alterar no editor, clique em <strong>Salvar no Servidor</strong> e <strong>Publicar Pagina</strong>.
        </p>

        <div class="top-actions">
            <a class="btn btn-edit" href="admin.php">+ Novo / abrir editor</a>
        </div>

        <?php if (!$projects): ?>
            <div class="empty">Nenhum produto no servidor ainda. Abra o editor, salve e publique.</div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preco</th>
                            <th>Status</th>
                            <th class="hide-sm">Atualizado</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projects as $p): ?>
                        <tr data-id="<?= (int) $p['id'] ?>">
                            <td>
                                <div style="font-weight:700"><?= h($p['title'] ?: $p['name']) ?></div>
                                <div class="mono"><?= h($p['name']) ?> · <?= h($p['url']) ?></div>
                            </td>
                            <td><?= moneyBr($p['price']) ?></td>
                            <td>
                                <?php if (!empty($p['is_active'])): ?>
                                    <span class="badge on">NO AR</span>
                                <?php else: ?>
                                    <span class="badge off">salvo</span>
                                <?php endif; ?>
                            </td>
                            <td class="hide-sm"><?= h($p['updated_at'] ?? '') ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-edit" href="admin.php?id=<?= (int) $p['id'] ?>">Editar</a>
                                    <a class="btn btn-view" href="<?= h($p['url']) ?>" target="_blank" rel="noopener">Ver pagina</a>
                                    <button type="button" class="btn btn-del btn-delete" data-id="<?= (int) $p['id'] ?>" data-name="<?= h($p['name']) ?>">Excluir</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const name = btn.dataset.name || id;
        if (!confirm('Excluir o produto "' + name + '" do servidor? A pagina /p/... deixa de funcionar.')) return;
        btn.disabled = true;
        try {
            const res = await fetch('delete_project.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: Number(id) })
            });
            const data = await res.json();
            if (data.success) {
                const row = btn.closest('tr');
                if (row) row.remove();
            } else {
                alert(data.message || 'Falha ao excluir');
                btn.disabled = false;
            }
        } catch (e) {
            alert('Erro de conexao');
            btn.disabled = false;
        }
    });
});
</script>
</body>
</html>
