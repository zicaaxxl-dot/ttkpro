<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, sans-serif;
        }

        body {
            padding: 16px;
            color: #111;
        }

        .placeholder-note {
            background: #fff7ed;
            border: 1px dashed #fb923c;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
            color: #9a3412;
            margin-bottom: 16px;
        }

        .badge {
            display: inline-block;
            background: #ffedd5;
            color: #c2410c;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 17px;
            margin-bottom: 10px;
        }

        .price-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 12px;
        }

        .price {
            font-size: 22px;
            font-weight: 800;
            color: #ea580c;
        }

        .price-old {
            font-size: 13px;
            color: #999;
            text-decoration: line-through;
        }

        .desc {
            font-size: 13px;
            color: #444;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .imgs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .imgs img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
        }
    </style>
</head>

<body>
    <div class="placeholder-note">
        Prévia simplificada — o template mobile completo (clone autoral estilo e-commerce) entra no Módulo 2.
    </div>
    <div id="content">
        <p style="color:#999;font-size:13px">Preencha os campos no painel à esquerda para ver a prévia.</p>
    </div>

    <script>
        window.addEventListener('message', (event) => {
            if (!event.data || event.data.type !== 'epro:update') return;
            render(event.data.project);
        });

        function render(p) {
            const b = p.basico || {};
            const imgs = (b.images || []).slice(0, 4).map(src => `<img src="${escapeAttr(src)}" loading="lazy">`).join('');
            document.getElementById('content').innerHTML = `
      ${b.badge ? `<span class="badge">${escapeHtml(b.badge)}</span>` : ''}
      <h1>${escapeHtml(b.title || 'Título do produto')}</h1>
      <div class="price-row">
        <span class="price">R$ ${Number(b.price || 0).toFixed(2).replace('.', ',')}</span>
        ${b.price_original ? `<span class="price-old">R$ ${Number(b.price_original).toFixed(2).replace('.', ',')}</span>` : ''}
      </div>
      <p class="desc">${escapeHtml(b.description || '')}</p>
      <div class="imgs">${imgs}</div>
    `;
        }

        function escapeHtml(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
        function escapeAttr(s) { return String(s ?? '').replace(/"/g, '&quot;'); }
    </script>
</body>

</html>