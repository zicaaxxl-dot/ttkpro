<?php
/**
 * functions.php
 * Camada de acesso a dados do sistema TTKPro.
 * Requer db.php (conexão PDO).
 */

require_once __DIR__ . '/db.php';

/* ============================================================
 * 1) GATEWAY (config global de pagamento — o que estava quebrado)
 * ============================================================ */

/**
 * Lê o gateway ativo (provider, public_key, secret_key).
 * É isso que o config.php deve chamar em vez de glob('*.json').
 */
function getGatewaySettings(): array
{
    $stmt = db()->query("SELECT provider, public_key, secret_key FROM gateway_settings WHERE id = 1");
    $row = $stmt->fetch();

    return $row ?: ['provider' => 'nexypay', 'public_key' => '', 'secret_key' => ''];
}

/**
 * Salva o gateway escolhido no admin.php. Sempre 1 linha (id=1).
 */
function saveGatewaySettings(string $provider, string $publicKey, string $secretKey): bool
{
    $provider = in_array($provider, ['nexypay', 'ecompag', 'blackcat', 'misticpay', 'pixzy'], true)
        ? $provider
        : 'nexypay';

    $stmt = db()->prepare("
        INSERT INTO gateway_settings (id, provider, public_key, secret_key)
        VALUES (1, :provider, :public_key, :secret_key)
        ON DUPLICATE KEY UPDATE
            provider   = VALUES(provider),
            public_key = VALUES(public_key),
            secret_key = VALUES(secret_key)
    ");

    return $stmt->execute([
        ':provider' => $provider,
        ':public_key' => trim($publicKey),
        ':secret_key' => trim($secretKey),
    ]);
}

/* ============================================================
 * 2) PROJETOS (o objeto `project` do admin.php)
 * ============================================================ */

/**
 * Cria ou atualiza um projeto inteiro (upsert por `name`), incluindo
 * todas as tabelas-filhas. Roda em transação: ou salva tudo, ou nada.
 *
 * $data é o MESMO objeto JSON que o admin.php monta em `project`
 * (basico, tracking, orderBumps, reviews, variationGroups, recommendations, footer).
 *
 * Retorna o id do projeto salvo.
 */

/**
 * O dump SQL original veio incompleto (sem PK/AUTO_INCREMENT nas tabelas
 * de projetos). Sem isso o INSERT do publish/save falha.
 */
function ensureProjectTablesSchema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $pdo = db();
    $statements = [
        "ALTER TABLE projects MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT",
        "ALTER TABLE projects ADD PRIMARY KEY (id)",
        "ALTER TABLE projects ADD UNIQUE KEY uniq_project_name (name)",
        "ALTER TABLE project_images MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT",
        "ALTER TABLE project_images ADD PRIMARY KEY (id)",
        "ALTER TABLE order_bumps MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT",
        "ALTER TABLE order_bumps ADD PRIMARY KEY (id)",
        "ALTER TABLE reviews MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT",
        "ALTER TABLE reviews ADD PRIMARY KEY (id)",
        "ALTER TABLE variation_groups MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT",
        "ALTER TABLE variation_groups ADD PRIMARY KEY (id)",
        "ALTER TABLE variation_options MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT",
        "ALTER TABLE variation_options ADD PRIMARY KEY (id)",
        "ALTER TABLE recommendations MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT",
        "ALTER TABLE recommendations ADD PRIMARY KEY (id)",
        "ALTER TABLE recommendation_variations MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT",
        "ALTER TABLE recommendation_variations ADD PRIMARY KEY (id)",
    ];
    foreach ($statements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // ja aplicado / duplicado — ignorar
        }
    }
    $done = true;
}
function saveProject(array $data): int
{
    ensureProjectTablesSchema();
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $basico = $data['basico'] ?? [];
        $tracking = $data['tracking'] ?? [];
        $footer = $data['footer'] ?? [];
        $name = trim($data['name'] ?? 'sem-nome');

        // upsert projects
        $stmt = $pdo->prepare("
            INSERT INTO projects (
                name, badge, title, price, price_original, discount_text, description,
                own_checkout, external_link, shop_name, shop_avatar,
                pixel_id, tiktok_pixel, gtm_id, social_proof, exit_intent,
                recommendations_discount,
                footer_razao, footer_cnpj, footer_email, footer_zap, footer_pol, footer_term
            ) VALUES (
                :name, :badge, :title, :price, :price_original, :discount_text, :description,
                :own_checkout, :external_link, :shop_name, :shop_avatar,
                :pixel_id, :tiktok_pixel, :gtm_id, :social_proof, :exit_intent,
                :rec_discount,
                :footer_razao, :footer_cnpj, :footer_email, :footer_zap, :footer_pol, :footer_term
            )
            ON DUPLICATE KEY UPDATE
                badge=VALUES(badge), title=VALUES(title), price=VALUES(price),
                price_original=VALUES(price_original), discount_text=VALUES(discount_text),
                description=VALUES(description), own_checkout=VALUES(own_checkout),
                external_link=VALUES(external_link), shop_name=VALUES(shop_name),
                shop_avatar=VALUES(shop_avatar), pixel_id=VALUES(pixel_id),
                tiktok_pixel=VALUES(tiktok_pixel), gtm_id=VALUES(gtm_id),
                social_proof=VALUES(social_proof), exit_intent=VALUES(exit_intent),
                recommendations_discount=VALUES(recommendations_discount),
                footer_razao=VALUES(footer_razao), footer_cnpj=VALUES(footer_cnpj),
                footer_email=VALUES(footer_email), footer_zap=VALUES(footer_zap),
                footer_pol=VALUES(footer_pol), footer_term=VALUES(footer_term)
        ");
        $stmt->execute([
            ':name' => $name,
            ':badge' => $basico['badge'] ?? '',
            ':title' => $basico['title'] ?? '',
            ':price' => $basico['price'] ?? 0,
            ':price_original' => $basico['price_original'] ?? 0,
            ':discount_text' => $basico['discount_text'] ?? '',
            ':description' => $basico['description'] ?? '',
            ':own_checkout' => !empty($basico['own_checkout']) ? 1 : 0,
            ':external_link' => $basico['external_link'] ?? '',
            ':shop_name' => $basico['shop_name'] ?? '',
            ':shop_avatar' => $basico['shop_avatar'] ?? '',
            ':pixel_id' => $tracking['pixel_id'] ?? '',
            ':tiktok_pixel' => $tracking['tiktok_pixel'] ?? '',
            ':gtm_id' => $tracking['gtm_id'] ?? '',
            ':social_proof' => !empty($tracking['social_proof']) ? 1 : 0,
            ':exit_intent' => !empty($tracking['exit_intent']) ? 1 : 0,
            ':rec_discount' => $data['recommendations']['discount'] ?? 40,
            ':footer_razao' => $footer['razao'] ?? '',
            ':footer_cnpj' => $footer['cnpj'] ?? '',
            ':footer_email' => $footer['email'] ?? '',
            ':footer_zap' => $footer['zap'] ?? '',
            ':footer_pol' => $footer['pol'] ?? '',
            ':footer_term' => $footer['term'] ?? '',
        ]);

        $projectId = (int) $pdo->lastInsertId();
        if ($projectId === 0) {
            // UPDATE não retorna lastInsertId; busca pelo name
            $sel = $pdo->prepare("SELECT id FROM projects WHERE name = :name");
            $sel->execute([':name' => $name]);
            $projectId = (int) $sel->fetchColumn();
        }

        // --- imagens: apaga e reinsere (mais simples que diff) ---
        $pdo->prepare("DELETE FROM project_images WHERE project_id = ?")->execute([$projectId]);
        $imgStmt = $pdo->prepare("INSERT INTO project_images (project_id, url, sort_order) VALUES (?, ?, ?)");
        foreach (($basico['images'] ?? []) as $i => $url) {
            if (trim($url) !== '')
                $imgStmt->execute([$projectId, $url, $i]);
        }

        // --- order bumps ---
        $pdo->prepare("DELETE FROM order_bumps WHERE project_id = ?")->execute([$projectId]);
        $bumpStmt = $pdo->prepare("
            INSERT INTO order_bumps (project_id, name, price, price_original, description, image, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach (($data['orderBumps'] ?? []) as $i => $b) {
            $bumpStmt->execute([
                $projectId,
                $b['name'] ?? '',
                $b['price'] ?? 0,
                $b['price_original'] ?? 0,
                $b['description'] ?? '',
                $b['image'] ?? '',
                $i,
            ]);
        }

        // --- avaliações ---
        $pdo->prepare("DELETE FROM reviews WHERE project_id = ?")->execute([$projectId]);
        $revStmt = $pdo->prepare("
            INSERT INTO reviews (project_id, name, stars, text, image, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach (($data['reviews'] ?? []) as $i => $r) {
            $revStmt->execute([
                $projectId,
                $r['name'] ?? '',
                $r['stars'] ?? 5,
                $r['text'] ?? '',
                $r['image'] ?? '',
                $i,
            ]);
        }

        // --- variações (grupos + opções) ---
        $pdo->prepare("DELETE FROM variation_groups WHERE project_id = ?")->execute([$projectId]);
        $groupStmt = $pdo->prepare("INSERT INTO variation_groups (project_id, name, sort_order) VALUES (?, ?, ?)");
        $optStmt = $pdo->prepare("
            INSERT INTO variation_options (group_id, value, price, image, checkout_url, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach (($data['variationGroups'] ?? []) as $gi => $group) {
            $groupStmt->execute([$projectId, $group['name'] ?? '', $gi]);
            $groupId = (int) $pdo->lastInsertId();
            foreach (($group['options'] ?? []) as $oi => $opt) {
                $optStmt->execute([
                    $groupId,
                    $opt['value'] ?? '',
                    $opt['price'] ?? 0,
                    $opt['image'] ?? '',
                    $opt['checkout_url'] ?? '',
                    $oi,
                ]);
            }
        }

        // --- recomendações (+ variações delas) ---
        $pdo->prepare("DELETE FROM recommendations WHERE project_id = ?")->execute([$projectId]);
        $recStmt = $pdo->prepare("
            INSERT INTO recommendations (project_id, title, price, image, checkout_url, variation_name, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $recVarStmt = $pdo->prepare("
            INSERT INTO recommendation_variations (recommendation_id, value, price, image, checkout_url, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach (($data['recommendations']['items'] ?? []) as $ri => $rec) {
            $recStmt->execute([
                $projectId,
                $rec['title'] ?? '',
                $rec['price'] ?? 0,
                $rec['image'] ?? '',
                $rec['checkout_url'] ?? '',
                $rec['variation_name'] ?? 'Modelo',
                $ri,
            ]);
            $recId = (int) $pdo->lastInsertId();
            foreach (($rec['variations'] ?? []) as $vi => $v) {
                $recVarStmt->execute([
                    $recId,
                    $v['value'] ?? '',
                    $v['price'] ?? 0,
                    $v['image'] ?? '',
                    $v['checkout_url'] ?? '',
                    $vi,
                ]);
            }
        }

        $pdo->commit();
        return $projectId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[ttkpro] Falha ao salvar projeto: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Marca um projeto como o "ativo" (o que template_lp.html / checkout.html
 * devem carregar). Só um por vez.
 */
function setActiveProject(int $projectId): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->exec("UPDATE projects SET is_active = 0 WHERE is_active = 1");
        $stmt = $pdo->prepare("UPDATE projects SET is_active = 1 WHERE id = ?");
        $stmt->execute([$projectId]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[ttkpro] Falha ao ativar projeto: ' . $e->getMessage());
        return false;
    }
}

/** Lista todos os projetos (id + name), para popular o <select> do admin. */
function listProjects(): array
{
    return db()->query("SELECT id, name, is_active FROM projects ORDER BY updated_at DESC")->fetchAll();
}

/** Remove um projeto e (por CASCADE) todos os dados filhos dele. */
function deleteProject(int $projectId): bool
{
    $stmt = db()->prepare("DELETE FROM projects WHERE id = ?");
    return $stmt->execute([$projectId]);
}

/**
 * Reconstrói o projeto no MESMO formato JSON que o admin.php usa em memória
 * (basico/tracking/orderBumps/reviews/variationGroups/recommendations/footer).
 * Use isso se quiser que o admin.php carregue projetos do banco em vez do localStorage.
 */
function getProjectFull(int $projectId): ?array
{
    $pdo = db();

    $p = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $p->execute([$projectId]);
    $row = $p->fetch();
    if (!$row)
        return null;

    $images = $pdo->prepare("SELECT url FROM project_images WHERE project_id = ? ORDER BY sort_order");
    $images->execute([$projectId]);

    $bumps = $pdo->prepare("SELECT name, price, price_original, description, image FROM order_bumps WHERE project_id = ? ORDER BY sort_order");
    $bumps->execute([$projectId]);

    $reviews = $pdo->prepare("SELECT name, stars, text, image FROM reviews WHERE project_id = ? ORDER BY sort_order");
    $reviews->execute([$projectId]);

    $groups = $pdo->prepare("SELECT id, name FROM variation_groups WHERE project_id = ? ORDER BY sort_order");
    $groups->execute([$projectId]);
    $variationGroups = [];
    foreach ($groups->fetchAll() as $g) {
        $opts = $pdo->prepare("SELECT value, price, image, checkout_url FROM variation_options WHERE group_id = ? ORDER BY sort_order");
        $opts->execute([$g['id']]);
        $variationGroups[] = ['name' => $g['name'], 'options' => $opts->fetchAll()];
    }

    $recs = $pdo->prepare("SELECT id, title, price, image, checkout_url, variation_name FROM recommendations WHERE project_id = ? ORDER BY sort_order");
    $recs->execute([$projectId]);
    $recommendationItems = [];
    foreach ($recs->fetchAll() as $r) {
        $vars = $pdo->prepare("SELECT value, price, image, checkout_url FROM recommendation_variations WHERE recommendation_id = ? ORDER BY sort_order");
        $vars->execute([$r['id']]);
        $recommendationItems[] = [
            'title' => $r['title'],
            'price' => (float) $r['price'],
            'image' => $r['image'],
            'checkout_url' => $r['checkout_url'],
            'variation_name' => $r['variation_name'],
            'variations' => $vars->fetchAll(),
        ];
    }

    return [
        'name' => $row['name'],
        'basico' => [
            'badge' => $row['badge'],
            'title' => $row['title'],
            'price' => (float) $row['price'],
            'price_original' => (float) $row['price_original'],
            'discount_text' => $row['discount_text'],
            'description' => $row['description'],
            'images' => array_column($images->fetchAll(), 'url'),
            'own_checkout' => (bool) $row['own_checkout'],
            'external_link' => $row['external_link'],
            'shop_name' => $row['shop_name'],
            'shop_avatar' => $row['shop_avatar'],
        ],
        'tracking' => [
            'pixel_id' => $row['pixel_id'],
            'tiktok_pixel' => $row['tiktok_pixel'],
            'gtm_id' => $row['gtm_id'],
            'social_proof' => (bool) $row['social_proof'],
            'exit_intent' => (bool) $row['exit_intent'],
        ],
        'orderBumps' => $bumps->fetchAll(),
        'reviews' => $reviews->fetchAll(),
        'variationGroups' => $variationGroups,
        'recommendations' => ['discount' => (float) $row['recommendations_discount'], 'items' => $recommendationItems],
        'footer' => [
            'razao' => $row['footer_razao'],
            'cnpj' => $row['footer_cnpj'],
            'email' => $row['footer_email'],
            'zap' => $row['footer_zap'],
            'pol' => $row['footer_pol'],
            'term' => $row['footer_term'],
        ],
    ];
}


/** Gera slug amigavel a partir do nome do projeto. */
function slugifyProjectName(string $name): string
{
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    if ($s === false) {
        $s = $name;
    }
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? $s : 'produto';
}

/** Busca projeto pelo slug (nome slugificado) ou pelo id numerico. */
function getProjectBySlug(string $slug): ?array
{
    $slug = trim($slug, '/');
    if ($slug === '') {
        return null;
    }

    if (ctype_digit($slug)) {
        return getProjectFull((int) $slug);
    }

    $rows = db()->query('SELECT id, name FROM projects')->fetchAll();
    foreach ($rows as $row) {
        if (slugifyProjectName($row['name']) === $slug) {
            return getProjectFull((int) $row['id']);
        }
    }
    return null;
}
/** Projeto atualmente ativo (usado pelas páginas públicas). */
function getActiveProject(): ?array
{
    $stmt = db()->query("SELECT id FROM projects WHERE is_active = 1 LIMIT 1");
    $row = $stmt->fetch();
    return $row ? getProjectFull((int) $row['id']) : null;
}

/* ============================================================
 * 3) PAGAMENTOS (substitui os arquivos em payments/pending e payments/paid)
 * ============================================================ */

function savePayment(string $paymentId, string $gateway, float $amount, ?int $projectId, array $customer = [], array $rawPayload = []): bool
{
    $stmt = db()->prepare("
        INSERT INTO payments (payment_id, project_id, gateway, status, amount, customer_name, customer_email, customer_cpf, raw_payload)
        VALUES (:payment_id, :project_id, :gateway, 'pending', :amount, :name, :email, :cpf, :raw)
    ");
    return $stmt->execute([
        ':payment_id' => $paymentId,
        ':project_id' => $projectId,
        ':gateway' => $gateway,
        ':amount' => $amount,
        ':name' => $customer['name'] ?? '',
        ':email' => $customer['email'] ?? '',
        ':cpf' => $customer['cpf'] ?? '',
        ':raw' => json_encode($rawPayload, JSON_UNESCAPED_UNICODE),
    ]);
}

function updatePaymentStatus(string $paymentId, string $status): bool
{
    $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;
    $stmt = db()->prepare("UPDATE payments SET status = ?, paid_at = ? WHERE payment_id = ?");
    return $stmt->execute([$status, $paidAt, $paymentId]);
}

function getPaymentById(string $paymentId): ?array
{
    $stmt = db()->prepare("SELECT * FROM payments WHERE payment_id = ?");
    $stmt->execute([$paymentId]);
    $row = $stmt->fetch();
    return $row ?: null;
}