<?php
/**
 * p.php — pagina publica do produto (injeta o JSON no template_lp.html).
 * Aceita: ?id=123  ou  ?s=slug  ou PATH_INFO / rewrite /p/slug
 */
require_once __DIR__ . '/functions.php';

$slug = '';
if (!empty($_GET['id'])) {
    $slug = (string) $_GET['id'];
} elseif (!empty($_GET['s'])) {
    $slug = (string) $_GET['s'];
} elseif (!empty($_SERVER['PATH_INFO'])) {
    $slug = trim($_SERVER['PATH_INFO'], '/');
} elseif (!empty($_GET['path'])) {
    $slug = (string) $_GET['path'];
}

// Apache rewrite: /p/meu-produto -> p.php?path=meu-produto
$project = $slug !== '' ? getProjectBySlug($slug) : getActiveProject();

if (!$project) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Produto nao encontrado</title></head><body style="font-family:sans-serif;padding:40px;text-align:center"><h1>Produto nao encontrado</h1><p>Publique o produto pelo admin primeiro.</p></body></html>';
    exit;
}

$templatePath = __DIR__ . '/template_lp.html';
$html = file_get_contents($templatePath);
if ($html === false) {
    http_response_code(500);
    echo 'Template ausente';
    exit;
}

$json = json_encode($project, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
$inject = "<base href=\"/\">\n<script>window.EPRO = {$json};</script>\n";

if (stripos($html, '<head>') !== false) {
    $html = preg_replace('/<head>/i', '<head>' . "\n" . $inject, $html, 1);
} else {
    $html = $inject . $html;
}

$title = htmlspecialchars($project['basico']['title'] ?? $project['name'] ?? 'Produto', ENT_QUOTES, 'UTF-8');
$html = preg_replace('/<title>.*?<\/title>/is', '<title>' . $title . '</title>', $html, 1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
echo $html;