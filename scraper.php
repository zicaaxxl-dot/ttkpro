<?php

/**
 * scraper.php — PRO v4.1 (PHP 7.4 Compatível + Sessão Correta)
 * RZ Tecnologia
 *
 * FIXES v4.1:
 *  - Mercado Livre: regex corrigida + busca de description via /description endpoint
 *  - WooCommerce: estratégia dedicada via API REST pública (?consumer_key não obrigatório)
 *  - Nuvemshop: estratégia dedicada via API pública JSON
 *  - Shopify: detecção ampliada para lojas com domínio próprio (via meta generator)
 *  - parseHtml: extração de imagens de og:image melhorada + WooCommerce price seletores
 */

// ── BLINDAGEM: qualquer erro PHP vira JSON ───────────────────────
ob_start();

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => "Erro PHP ($errno): $errstr (linha $errline de " . basename($errfile) . ")"]);
    exit;
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Erro fatal PHP: ' . $err['message']]);
    }
});

// ── SESSÃO ───────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name('epro_admin');
    session_start();
}

$configFile = __DIR__ . '/includes/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

if (empty($_SESSION['admin_logged_in'])) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Atualize a página e faça login novamente (F5 → Login).']);
    exit;
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

if (!function_exists('curl_init')) {
    echo json_encode(['success' => false, 'message' => 'A extensão cURL não está habilitada no servidor PHP.']);
    exit;
}

// ── Lê e valida URL ──────────────────────────────────────────────
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$url = trim($input['url'] ?? '');

if (empty($url)) {
    echo json_encode(['success' => false, 'message' => 'Informe uma URL de produto.']);
    exit;
}

if (!preg_match('#^https?://#i', $url)) {
    $url = 'https://' . $url;
}

$parts = parse_url($url);
if (!$parts || empty($parts['host'])) {
    echo json_encode(['success' => false, 'message' => 'URL inválida.']);
    exit;
}

$host = strtolower($parts['host']);

/* ═══════════════════════════════════════════════════════════════
   ESTRATÉGIA 1 — TIKTOK SHOP
   ═══════════════════════════════════════════════════════════════ */
if (strpos($host, 'tiktok.com') !== false && preg_match('#/(\d{10,})#', $url, $m)) {
    $productId = $m[1];
    preg_match('#shop\.tiktok\.com/([a-z]{2})#i', $url, $regionM);
    $region = strtoupper(isset($regionM[1]) ? $regionM[1] : 'BR');

    $apiEndpoints = [
        "https://shop.tiktok.com/api/v1/product/detail?product_id={$productId}&region={$region}&language=pt",
        "https://shop.tiktok.com/api/v2/product/detail?product_id={$productId}&region={$region}",
    ];

    foreach ($apiEndpoints as $apiUrl) {
        $body = curlFetch($apiUrl, [
            'Accept: application/json',
            'Accept-Language: pt-BR,pt;q=0.9',
            "Referer: {$url}",
            'Origin: https://shop.tiktok.com',
            'X-Requested-With: XMLHttpRequest',
        ]);
        if (!$body)
            continue;
        $json = json_decode($body, true);
        if (!$json || empty($json['data']['product']))
            continue;
        $extracted = extractTikTokProduct($json['data']['product']);
        if (!empty($extracted['title']))
            jsonSuccess($extracted);
    }

    $html = curlFetch($url, tiktokBrowserHeaders($url));
    if ($html) {
        $result = extractNextData($html);
        if (empty($result['title']) && empty($result['images'])) {
            $result = parseHtml($html, $url);
        }
        if (!empty($result['title']) || !empty($result['images'])) {
            jsonSuccess($result);
        }
    }

    jsonError('O TikTok Shop está bloqueando acesso automático. Use produtos de outras lojas ou preencha manualmente.');
}

/* ═══════════════════════════════════════════════════════════════
   ESTRATÉGIA 2 — MERCADO LIVRE (API pública)
   ═══════════════════════════════════════════════════════════════ */
if (strpos($host, 'mercadolivre') !== false || strpos($host, 'mercadolibre') !== false) {

    // Suporta formatos:
    //   /MLB123456789
    //   /p/MLB123456789
    //   /produto/MLB123456789-titulo
    $itemId = null;

    if (preg_match('#[/-](MLB\d+)#i', $url, $mlM)) {
        $itemId = strtoupper($mlM[1]);
    } elseif (preg_match('#/(\d{8,})#', $url, $mlM2)) {
        $itemId = 'MLB' . $mlM2[1];
    }

    if ($itemId) {
        // Dados principais
        $body = curlFetch("https://api.mercadolibre.com/items/{$itemId}", ['Accept: application/json']);
        if ($body) {
            $d = json_decode($body, true);
            if (!empty($d['id'])) {
                $imgs = [];
                foreach ($d['pictures'] ?? [] as $pic) {
                    // Prioriza URL de alta resolução
                    $imgUrl = $pic['url'] ?? ($pic['secure_url'] ?? '');
                    // Converte para tamanho maior: -O -> -D (800x800 -> original)
                    $imgUrl = preg_replace('/-[A-Z]\.jpg$/i', '-O.jpg', $imgUrl);
                    if ($imgUrl)
                        $imgs[] = $imgUrl;
                }

                // Busca descrição em endpoint separado
                $desc = '';
                $descBody = curlFetch("https://api.mercadolibre.com/items/{$itemId}/description", ['Accept: application/json']);
                if ($descBody) {
                    $descData = json_decode($descBody, true);
                    $desc = trim($descData['plain_text'] ?? '');
                }

                jsonSuccess([
                    'title' => $d['title'] ?? '',
                    'price' => isset($d['price']) ? floatval($d['price']) : null,
                    'price_original' => isset($d['original_price']) ? floatval($d['original_price']) : null,
                    'description' => $desc,
                    'images' => $imgs,
                    'reviews' => [],
                ]);
            }
        }
    }
    // Se não encontrou pelo ID, cai no fallback HTML
}

/* ═══════════════════════════════════════════════════════════════
   ESTRATÉGIA 3 — SHOPIFY (endpoint .json nativo)
   ═══════════════════════════════════════════════════════════════ */

// Detecção ampliada: myshopify.com OU /products/ na URL OU meta generator no HTML
$isShopify = (strpos($host, 'myshopify.com') !== false || strpos($url, '/products/') !== false);

if (!$isShopify) {
    // Verificação rápida via meta generator (para lojas com domínio próprio)
    $htmlCheck = curlFetch($url, browserHeaders($url), 10);
    if ($htmlCheck && stripos($htmlCheck, 'shopify') !== false) {
        $isShopify = true;
    }
}

if ($isShopify && strpos($url, '/products/') !== false) {
    // Remove query string e fragmento, adiciona .json
    $jsonUrl = preg_replace('#(\?.*|#.*)$#', '', $url) . '.json';
    $body = curlFetch($jsonUrl, browserHeaders($url));
    if ($body) {
        $data = json_decode($body, true);
        if (!empty($data['product'])) {
            $p = $data['product'];
            $images = [];
            foreach ($p['images'] ?? [] as $img) {
                $src = $img['src'] ?? '';
                // Remove parâmetros de tamanho do Shopify (ex: _200x200)
                $src = preg_replace('#_\d+x\d+(\.[a-z]+)$#i', '$1', $src);
                if ($src)
                    $images[] = $src;
            }
            $price = isset($p['variants'][0]['price']) ? floatval($p['variants'][0]['price']) : 0;
            $cmp = isset($p['variants'][0]['compare_at_price']) ? floatval($p['variants'][0]['compare_at_price']) : 0;

            // Extrai variações
            $reviews = [];

            jsonSuccess([
                'title' => $p['title'] ?? '',
                'price' => $price ?: null,
                'price_original' => $cmp ?: null,
                'description' => strip_tags($p['body_html'] ?? ''),
                'images' => $images,
                'reviews' => $reviews,
            ]);
        }
    }
}

/* ═══════════════════════════════════════════════════════════════
   ESTRATÉGIA 4 — WOOCOMMERCE (API REST pública /wp-json/wc/v3)
   ═══════════════════════════════════════════════════════════════ */
$isWoo = false;

// Detecta WooCommerce por padrões comuns de URL
if (
    strpos($url, '?p=') !== false ||
    strpos($url, '/produto/') !== false ||
    strpos($url, '/product/') !== false ||
    preg_match('#/loja/|/shop/|/wc-api/#', $url)
) {
    $isWoo = true;
}

if ($isWoo) {
    // Tenta extrair ID do produto da URL (?p=ID ou /produto/slug)
    $wooProductId = null;
    if (preg_match('#[?&]p=(\d+)#', $url, $wm)) {
        $wooProductId = $wm[1];
    }

    $scheme = $parts['scheme'] ?? 'https';
    $wooBase = $scheme . '://' . $host;

    // Estratégia A: API REST pública com slug
    $slug = '';
    if (!empty($parts['path'])) {
        $pathParts = explode('/', trim($parts['path'], '/'));
        $slug = end($pathParts);
    }

    $wooApiUrls = [];
    if ($wooProductId) {
        $wooApiUrls[] = $wooBase . '/wp-json/wc/v3/products/' . $wooProductId;
        $wooApiUrls[] = $wooBase . '/wp-json/wc/v2/products/' . $wooProductId;
    }
    if ($slug) {
        $wooApiUrls[] = $wooBase . '/wp-json/wc/v3/products?slug=' . urlencode($slug);
        $wooApiUrls[] = $wooBase . '/wp-json/wc/v2/products?slug=' . urlencode($slug);
    }
    // Fallback: API sem autenticação (algumas lojas permitem)
    $wooApiUrls[] = $wooBase . '/wp-json/wc/store/v1/products?search=' . urlencode($slug);

    foreach ($wooApiUrls as $wooUrl) {
        $body = curlFetch($wooUrl, array_merge(browserHeaders($url), ['Accept: application/json']));
        if (!$body)
            continue;
        $d = json_decode($body, true);
        if (!$d)
            continue;

        // Resposta pode ser array (lista) ou objeto (único)
        if (isset($d[0]))
            $d = $d[0];

        if (!empty($d['name']) || !empty($d['title'])) {
            $name = $d['name'] ?? ($d['title']['rendered'] ?? '');
            $price = floatval($d['price'] ?? ($d['sale_price'] ?? 0));
            $orig = floatval($d['regular_price'] ?? 0);
            $desc = strip_tags($d['description'] ?? ($d['short_description'] ?? ''));
            $images = [];

            // Imagens WooCommerce
            foreach ($d['images'] ?? [] as $img) {
                $src = $img['src'] ?? '';
                if ($src)
                    $images[] = $src;
            }
            // Store API usa estrutura diferente
            if (empty($images) && !empty($d['images'])) {
                foreach ($d['images'] as $img) {
                    if (is_string($img))
                        $images[] = $img;
                    elseif (isset($img['full']))
                        $images[] = $img['full'];
                }
            }

            if ($name) {
                jsonSuccess([
                    'title' => $name,
                    'price' => $price ?: null,
                    'price_original' => ($orig > $price && $orig > 0) ? $orig : null,
                    'description' => $desc,
                    'images' => $images,
                    'reviews' => [],
                ]);
            }
        }
    }
    // Se API falhar, cai no fallback HTML com parseadores específicos para WooCommerce
}

/* ═══════════════════════════════════════════════════════════════
   ESTRATÉGIA 5 — NUVEMSHOP (API pública JSON)
   ═══════════════════════════════════════════════════════════════ */
$isNuvem = (
    strpos($host, 'nuvemshop.com.br') !== false ||
    strpos($host, 'tiendanube.com') !== false ||
    strpos($host, 'myinstant.com') !== false
);

if (!$isNuvem) {
    // Verifica meta generator ou scripts característicos
    if (!empty($htmlCheck) && (stripos($htmlCheck, 'nuvemshop') !== false || stripos($htmlCheck, 'tiendanube') !== false)) {
        $isNuvem = true;
    }
}

if ($isNuvem) {
    // Tenta endpoint JSON nativo da Nuvemshop
    $nuvemJsonUrl = rtrim($url, '/') . '.json';
    $body = curlFetch($nuvemJsonUrl, browserHeaders($url));
    if ($body) {
        $d = json_decode($body, true);
        if (!empty($d['name']) || !empty($d['id'])) {
            $images = [];
            foreach ($d['images'] ?? [] as $img) {
                $src = '';
                if (is_string($img)) {
                    $src = $img;
                } elseif (isset($img['src'])) {
                    $src = $img['src'];
                } elseif (isset($img['url'])) {
                    $src = $img['url'];
                }
                // Remove parâmetros de tamanho da Nuvemshop
                $src = preg_replace('#\?.*$#', '', $src);
                if ($src)
                    $images[] = $src;
            }

            // Preço: Nuvemshop retorna em centavos (inteiro) ou string
            $variants = $d['variants'] ?? [];
            $priceRaw = 0;
            $origRaw = 0;
            if (!empty($variants)) {
                $v = $variants[0];
                $priceRaw = $v['price'] ?? ($d['price'] ?? 0);
                $origRaw = $v['compare_at_price'] ?? ($d['compare_at_price'] ?? 0);
            } else {
                $priceRaw = $d['price'] ?? 0;
                $origRaw = $d['compare_at_price'] ?? 0;
            }

            $price = floatval(str_replace(',', '.', $priceRaw));
            $orig = floatval(str_replace(',', '.', $origRaw));

            // Nuvemshop às vezes retorna em centavos
            if ($price > 10000) {
                $price = round($price / 100, 2);
                $orig = round($orig / 100, 2);
            }

            $desc = '';
            if (!empty($d['description'])) {
                $desc = strip_tags(is_array($d['description']) ? ($d['description']['pt'] ?? reset($d['description'])) : $d['description']);
            }

            $name = is_array($d['name']) ? ($d['name']['pt'] ?? reset($d['name'])) : ($d['name'] ?? '');

            if ($name) {
                jsonSuccess([
                    'title' => $name,
                    'price' => $price ?: null,
                    'price_original' => ($orig > $price && $orig > 0) ? $orig : null,
                    'description' => $desc,
                    'images' => $images,
                    'reviews' => [],
                ]);
            }
        }
    }
}

/* ═══════════════════════════════════════════════════════════════
   ESTRATÉGIA 6 — HTML GENÉRICO (qualquer loja)
   ═══════════════════════════════════════════════════════════════ */
$html = isset($htmlCheck) && $htmlCheck ? $htmlCheck : curlFetch($url, browserHeaders($url));

if (!$html) {
    jsonError('Não foi possível acessar a página. O servidor pode ter bloqueado a requisição ou a URL está incorreta.');
}

// Tenta __NEXT_DATA__ / __INITIAL_STATE__ (SPAs React/Next.js)
$result = extractNextData($html);

// Fallback HTML puro
if (empty($result['title']) && empty($result['images'])) {
    $result = parseHtml($html, $url);
}

if (empty($result['title']) && empty($result['images'])) {
    preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $titleM);
    $pageTitle = isset($titleM[1]) ? trim($titleM[1]) : '(sem título)';
    jsonError("Nenhum dado de produto encontrado. Título da página: \"{$pageTitle}\". Certifique-se de usar a URL direta de um produto.");
}

jsonSuccess($result);


/* ═══════════════════════════════════════════════════════════════
   FUNÇÕES AUXILIARES
   ═══════════════════════════════════════════════════════════════ */

function jsonSuccess(array $d)
{
    echo json_encode([
        'success' => true,
        'data' => [
            'title' => trim($d['title'] ?? ''),
            'price' => isset($d['price']) && floatval($d['price']) > 0 ? floatval($d['price']) : null,
            'price_original' => isset($d['price_original']) && floatval($d['price_original']) > 0 ? floatval($d['price_original']) : null,
            'description' => trim($d['description'] ?? ''),
            'images' => array_values(array_unique(array_filter((array) ($d['images'] ?? [])))),
            'reviews' => array_values((array) ($d['reviews'] ?? [])),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError($msg)
{
    echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function curlFetch($url, array $headers = [], $timeout = 20)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => array_values(array_filter($headers)),
        CURLOPT_COOKIEFILE => '',
        CURLOPT_COOKIEJAR => '',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || empty($body) || $code >= 500)
        return null;
    if ($code === 401 || $code === 403)
        return null; // API sem permissão
    return $body;
}

function browserHeaders($referer = '')
{
    return array_filter([
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Sec-Ch-Ua: "Chromium";v="124","Google Chrome";v="124","Not-A.Brand";v="99"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Upgrade-Insecure-Requests: 1',
        $referer ? "Referer: {$referer}" : '',
    ]);
}

function tiktokBrowserHeaders($url)
{
    return [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: pt-BR,pt;q=0.9',
        'Referer: https://www.tiktok.com/',
        'Sec-Ch-Ua: "Chromium";v="124","Google Chrome";v="124"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: cross-site',
        'Upgrade-Insecure-Requests: 1',
        'Cookie: tt_chain_token=; msToken=; ttwid=1',
    ];
}

function extractTikTokProduct(array $pd)
{
    $images = [];
    foreach ($pd['images'] ?? [] as $img) {
        $u = isset($img['origin_url']) ? $img['origin_url'] : (isset($img['thumb_url_list'][0]) ? $img['thumb_url_list'][0] : '');
        if ($u)
            $images[] = $u;
    }

    $priceRaw = isset($pd['price_info']['price'])
        ? $pd['price_info']['price']
        : (isset($pd['sku_list'][0]['price_info']['price']) ? $pd['sku_list'][0]['price_info']['price'] : 0);
    $price = (is_int($priceRaw) && $priceRaw > 10000) ? round($priceRaw / 100, 2) : floatval($priceRaw);

    $origRaw = isset($pd['price_info']['market_price'])
        ? $pd['price_info']['market_price']
        : (isset($pd['sku_list'][0]['price_info']['market_price']) ? $pd['sku_list'][0]['price_info']['market_price'] : 0);
    $orig = (is_int($origRaw) && $origRaw > 10000) ? round($origRaw / 100, 2) : floatval($origRaw);

    $reviews = [];
    foreach ($pd['reviews'] ?? [] as $r) {
        $reviews[] = [
            'name' => isset($r['user']['nickname']) ? $r['user']['nickname'] : 'Cliente',
            'stars' => intval(isset($r['score']) ? $r['score'] : 5),
            'text' => isset($r['content']) ? $r['content'] : '',
            'image' => isset($r['images'][0]['origin_url']) ? $r['images'][0]['origin_url'] : '',
        ];
    }

    return [
        'title' => isset($pd['title']) ? $pd['title'] : '',
        'price' => $price ?: null,
        'price_original' => $orig ?: null,
        'description' => strip_tags(isset($pd['description']) ? $pd['description'] : ''),
        'images' => $images,
        'reviews' => $reviews,
    ];
}

function extractNextData($html)
{
    $result = ['title' => '', 'price' => null, 'price_original' => null, 'description' => '', 'images' => [], 'reviews' => []];

    if (preg_match('#<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(\{.+?\})</script>#s', $html, $m)) {
        $data = json_decode($m[1], true);
        if ($data)
            return searchJsonForProduct($data, $result);
    }

    $patterns = [
        '#window\.__INITIAL_STATE__\s*=\s*(\{.+?\});\s*(?:</script>|window\.)#s',
        '#window\.__data__\s*=\s*(\{.+?\});\s*(?:</script>|window\.)#s',
        '#window\.__STATE__\s*=\s*(\{.+?\});\s*(?:</script>|window\.)#s',
        '#window\.__NUXT__\s*=\s*(\{.+?\});\s*(?:</script>|window\.)#s',
        '#<script[^>]+type=["\']application/json["\'][^>]*>(\{.+?\})</script>#s',
    ];
    foreach ($patterns as $pat) {
        if (preg_match($pat, $html, $m)) {
            $data = json_decode($m[1], true);
            if ($data) {
                $r = searchJsonForProduct($data, $result);
                if (!empty($r['title']) || !empty($r['images']))
                    return $r;
            }
        }
    }

    return $result;
}

function searchJsonForProduct(array $data, array $result, $depth = 0)
{
    if ($depth > 8)
        return $result;

    $titleKeys = ['title', 'name', 'productName', 'product_name', 'itemName', 'item_name'];
    $priceKeys = ['price', 'salePrice', 'sale_price', 'currentPrice', 'current_price', 'sellPrice', 'sell_price'];
    $origKeys = ['originalPrice', 'original_price', 'marketPrice', 'market_price', 'compareAtPrice', 'compare_at_price', 'listPrice', 'list_price', 'regularPrice', 'regular_price'];
    $descKeys = ['description', 'body_html', 'detail', 'content', 'summary', 'shortDescription', 'short_description'];
    $imageKeys = ['image', 'images', 'imageUrl', 'image_url', 'thumbnail', 'cover', 'mainImage', 'main_image', 'picUrl', 'pic_url'];

    foreach ($data as $key => $val) {
        if (is_string($val) && !empty($val)) {
            if (empty($result['title']) && in_array($key, $titleKeys) && strlen($val) > 3)
                $result['title'] = $val;
            if (empty($result['price']) && in_array($key, $priceKeys))
                $result['price'] = floatClean((string) $val);
            if (empty($result['price_original']) && in_array($key, $origKeys))
                $result['price_original'] = floatClean((string) $val);
            if (empty($result['description']) && in_array($key, $descKeys) && strlen($val) > 10)
                $result['description'] = strip_tags($val);
            if (in_array($key, $imageKeys) && filter_var($val, FILTER_VALIDATE_URL) && count($result['images']) < 10)
                $result['images'][] = $val;
        }
        if (is_array($val)) {
            if (in_array($key, $imageKeys)) {
                foreach ($val as $item) {
                    if (is_string($item)) {
                        $imgUrl = $item;
                    } else {
                        $imgUrl = isset($item['url']) ? $item['url']
                            : (isset($item['src']) ? $item['src']
                                : (isset($item['origin_url']) ? $item['origin_url'] : ''));
                    }
                    if ($imgUrl && filter_var($imgUrl, FILTER_VALIDATE_URL) && count($result['images']) < 10)
                        $result['images'][] = $imgUrl;
                }
            }
            $result = searchJsonForProduct($val, $result, $depth + 1);
        }
        if (is_numeric($val) && $val > 0) {
            if (empty($result['price']) && in_array($key, $priceKeys))
                $result['price'] = floatClean((string) $val);
            if (empty($result['price_original']) && in_array($key, $origKeys))
                $result['price_original'] = floatClean((string) $val);
        }
    }
    return $result;
}

function parseHtml($html, $baseUrl)
{
    $result = ['title' => null, 'price' => null, 'price_original' => null, 'description' => null, 'images' => [], 'reviews' => []];

    $blockedTitles = ['Security Check', 'Just a moment...', 'Access Denied', 'Attention Required!', 'Robot or human?', 'DDoS protection', 'Cloudflare'];
    if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $tm)) {
        foreach ($blockedTitles as $bt) {
            if (stripos($tm[1], $bt) !== false)
                return $result;
        }
    }

    if (!class_exists('DOMDocument'))
        return $result;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    $x = new DOMXPath($dom);

    // JSON-LD
    foreach ($x->query('//script[@type="application/ld+json"]') as $script) {
        $raw = trim($script->textContent);
        if (empty($raw))
            continue;
        $data = json_decode($raw, true);
        if (!$data)
            continue;
        $items = isset($data['@graph']) ? $data['@graph'] : (isset($data[0]) ? $data : [$data]);
        foreach ($items as $item) {
            if (!isset($item['@type']))
                continue;
            $types = (array) $item['@type'];
            if (!array_intersect($types, ['Product', 'IndividualProduct', 'ProductGroup']))
                continue;
            if (empty($result['title']) && !empty($item['name']))
                $result['title'] = $item['name'];
            if (empty($result['description']) && !empty($item['description']))
                $result['description'] = strip_tags($item['description']);
            $imgField = isset($item['image']) ? $item['image'] : [];
            if (is_string($imgField))
                $imgField = [$imgField];
            foreach ($imgField as $img) {
                $u = is_array($img) ? ($img['url'] ?? '') : $img;
                if ($u && count($result['images']) < 10)
                    $result['images'][] = $u;
            }
            $offers = isset($item['offers']) ? $item['offers'] : [];
            if (isset($offers['price']))
                $offers = [$offers];
            foreach ((array) $offers as $offer) {
                if (empty($result['price']) && isset($offer['price']))
                    $result['price'] = floatClean((string) $offer['price']);
                if (empty($result['price_original']) && isset($offer['highPrice']))
                    $result['price_original'] = floatClean((string) $offer['highPrice']);
            }
        }
    }

    // Open Graph / Twitter / Product meta
    $metaMap = ['og:title' => 'title', 'og:description' => 'description', 'twitter:title' => 'title'];
    $priceOGK = ['product:price:amount', 'og:price:amount', 'product:sale_price:amount'];
    $origOGK = ['product:original_price:amount', 'product:price:regular_amount'];
    foreach ($x->query('//meta[@property or @name]') as $meta) {
        $key = $meta->getAttribute('property') ?: $meta->getAttribute('name');
        $val = trim($meta->getAttribute('content'));
        if (!$val)
            continue;
        if (isset($metaMap[$key]) && empty($result[$metaMap[$key]]))
            $result[$metaMap[$key]] = $val;
        if (in_array($key, ['og:image', 'twitter:image', 'og:image:secure_url']) && count($result['images']) < 10)
            $result['images'][] = $val;
        if (in_array($key, $priceOGK) && empty($result['price']))
            $result['price'] = floatClean($val);
        if (in_array($key, $origOGK) && empty($result['price_original']))
            $result['price_original'] = floatClean($val);
    }

    // Título fallback
    if (empty($result['title'])) {
        foreach (['//h1[1]', '//title[1]'] as $sel) {
            $n = $x->query($sel);
            if ($n && $n->length && strlen(trim($n->item(0)->textContent)) > 3) {
                $result['title'] = trim($n->item(0)->textContent);
                break;
            }
        }
    }

    // Preço heurístico — inclui seletores WooCommerce e Nuvemshop
    if (empty($result['price'])) {
        $priceSelectors = [
            '//*[@data-product-price][1]',
            '//*[@data-price][1]',
            '//*[contains(@class,"price--main")][1]',
            '//*[contains(@class,"product-price")][1]',
            '//*[contains(@class,"woocommerce-Price-amount")][1]',
            '//*[contains(@class,"price-item--sale")][1]',
            '//*[contains(@class,"js-price-display")][1]',
            '//*[@itemprop="price"][1]',
        ];
        foreach ($priceSelectors as $sel) {
            $n = $x->query($sel);
            if ($n && $n->length) {
                $raw = $n->item(0)->getAttribute('data-product-price')
                    ?: $n->item(0)->getAttribute('data-price')
                    ?: $n->item(0)->getAttribute('content')
                    ?: trim($n->item(0)->textContent);
                $p = floatClean($raw);
                if ($p > 0) {
                    $result['price'] = $p;
                    break;
                }
            }
        }
    }

    // Imagens adicionais via tag <img> com classe de produto
    if (count($result['images']) < 3) {
        $imgSelectors = [
            '//*[contains(@class,"product-image")]//img',
            '//*[contains(@class,"woocommerce-product-gallery")]//img',
            '//*[contains(@class,"product__media")]//img',
        ];
        foreach ($imgSelectors as $sel) {
            foreach ($x->query($sel) as $img) {
                $src = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: $img->getAttribute('data-lazy-src');
                if ($src && filter_var($src, FILTER_VALIDATE_URL) && count($result['images']) < 10) {
                    $result['images'][] = $src;
                }
            }
        }
    }

    $result['images'] = array_values(array_unique(array_filter($result['images'])));
    return $result;
}

function floatClean($v)
{
    $v = trim($v);
    $clean = preg_replace('/[^\d,.]/', '', $v);
    if ($clean === '')
        return null;
    // Formato BR: 1.299,90
    if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $clean)) {
        $clean = str_replace(['.', ','], ['', '.'], $clean);
    } else {
        $clean = str_replace(',', '', $clean);
    }
    $f = floatval($clean);
    if ($f <= 0)
        return null;
    if ($f > 100000)
        $f = round($f / 100, 2);
    return $f;
}