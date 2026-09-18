<?php
// products/index.php
$titulo = 'Editor PRO - Produtos';
$menuAtivo = 'produtos';
include '../includes/header.php';

// Simulação de banco de dados para a listagem inicial
$produtos = [
    [
        'id' => 1,
        'nome' => 'Kit de roupas Heringer',
        'preco' => '90,00',
        'estoque' => 222,
        'imagem' => ''
    ],
    [
        'id' => 2,
        'nome' => 'Kit Serra Elétrica Tico-Tico',
        'preco' => '5,00',
        'estoque' => 233,
        'imagem' => 'https://via.placeholder.com/64/FFFFFF/000000?text=Serra'
    ]
];
?>

<style>
    /* Estilos auxiliares específicos para o Editor que o Tailwind não cobre diretamente */
    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease-in-out;
    }

    .iframe-mobile {
        width: 375px;
        height: 750px;
        border: 8px solid #111827;
        border-radius: 2rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        background-color: #fff;
    }

    .iframe-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 1rem 0;
    }

    /* Checkbox de marketing customizado */
    .checkbox-custom {
        width: 1.25rem;
        height: 1.25rem;
        accent-color: #059669;
        cursor: pointer;
        margin-top: 0.1rem;
    }

    /* Estilos do Toast */
    .toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 9999;
    }

    .toast {
        background: #fff;
        border-left: 4px solid #111827;
        border-radius: 6px;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
        animation: toastIn .3s ease;
        color: #111827;
    }

    .toast.success {
        border-left-color: #10b981;
    }

    .toast.error {
        border-left-color: #ff4d4f;
    }

    @keyframes toastIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div id="view-lista" class="p-8 max-w-7xl mx-auto fade-in">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Produtos (<?php echo count($produtos); ?>)</h1>
        <div class="flex gap-3">
            <button onclick="toggleView('novo')"
                class="flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="ph ph-plus text-lg"></i> Novo Produto
            </button>
            <button onclick="window.pasteFromExtensionMain()"
                class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="ph ph-download-simple text-lg"></i> Importar do TikTok
            </button>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        <?php foreach ($produtos as $prod): ?>
            <div
                class="bg-white border border-gray-200 rounded-xl p-4 flex items-center justify-between hover:shadow-sm transition-all group">
                <div class="flex items-center gap-4">
                    <?php if (empty($prod['imagem'])): ?>
                        <div
                            class="w-16 h-16 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center text-gray-400">
                            <i class="ph ph-image text-2xl"></i></div>
                    <?php else: ?>
                        <img src="<?php echo $prod['imagem']; ?>"
                            class="w-16 h-16 object-cover rounded-lg border border-gray-100 flex-shrink-0">
                    <?php endif; ?>
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-1 max-w-2xl truncate cursor-pointer hover:text-brand-green"
                            onclick="toggleView('novo')"><?php echo $prod['nome']; ?></h3>
                        <p class="text-sm text-gray-500 font-medium">R$ <?php echo $prod['preco']; ?> <span
                                class="text-gray-300 mx-1">&bull;</span> <?php echo $prod['estoque']; ?> em estoque</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button class="p-2 hover:bg-gray-100 hover:text-gray-900 rounded-md transition-colors"
                        title="Duplicar"><i class="ph ph-copy text-lg"></i></button>
                    <button onclick="toggleView('novo')"
                        class="p-2 hover:bg-gray-100 hover:text-brand-green rounded-md transition-colors" title="Editar"><i
                            class="ph ph-pencil-simple text-lg"></i></button>
                    <button class="p-2 hover:bg-red-50 hover:text-red-600 text-red-500 rounded-md transition-colors"
                        title="Excluir"><i class="ph ph-trash text-lg"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="view-editor" class="p-8 max-w-[1700px] mx-auto hidden fade-in">

    <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-200">
        <div class="flex items-center gap-4">
            <button onclick="toggleView('lista')"
                class="p-2 hover:bg-gray-100 text-gray-500 hover:text-gray-900 rounded-full transition-colors">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-900">Editor de Produto PRO</h1>
        </div>
        <div class="flex gap-3">
            <select id="projectSelect" class="input-field w-48 py-1.5 h-10 bg-gray-50"></select>
            <button id="btnNewProject"
                class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50">Novo</button>
            <button id="btnSaveProject"
                class="flex items-center gap-2 px-6 py-2 bg-brand-green text-white text-sm font-semibold rounded-lg hover:bg-green-600 shadow-sm transition-colors">
                <i class="ph ph-floppy-disk text-lg"></i> Salvar
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">

        <div class="xl:col-span-7 space-y-6">

            <div class="bg-gray-900 rounded-xl p-5 shadow-sm text-white">
                <div class="flex items-center gap-2 font-bold mb-3">
                    <i class="ph ph-tiktok-logo text-xl"></i> Importar da Extensão TikTok Shop
                </div>
                <div class="flex flex-wrap gap-3 mb-4">
                    <button
                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-bold uppercase rounded-lg transition-colors"
                        id="btnPasteMain">Colar PRINCIPAL</button>
                    <button
                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold uppercase rounded-lg transition-colors"
                        id="btnPasteRec">Colar RECOMENDAÇÃO</button>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-400">
                    <a href="extension/ttkpro-extractor.zip" download class="hover:text-white underline"><i
                            class="ph ph-download-simple"></i> Baixar Extensão</a>
                    <button id="btnHowToInstall" class="hover:text-white underline"><i class="ph ph-question"></i> Como
                        Instalar</button>
                    <label class="hover:text-white underline cursor-pointer"><i class="ph ph-upload-simple"></i>
                        Importar JSON <input type="file" id="btnImportJson" accept=".json" class="hidden"></label>
                    <button id="btnExportJson" class="hover:text-white underline"><i class="ph ph-export"></i> Exportar
                        JSON</button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-2" id="editorTabs">
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg bg-gray-900 text-white transition-colors"
                    data-tab="basico">Básico</button>
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                    data-tab="marketing">Marketing</button>
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                    data-tab="gateway">Pagamento</button>
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                    data-tab="orderbump">Order Bump</button>
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                    data-tab="midia">Mídia</button>
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                    data-tab="variacoes">Variações</button>
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                    data-tab="avaliacoes">Avaliações</button>
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                    data-tab="recomendacoes">Recomendações</button>
                <button
                    class="tab-btn px-4 py-2 text-sm font-semibold rounded-lg text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                    data-tab="rodape">Rodapé</button>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm">

                <div class="tab-content active p-6" data-tab-content="basico">

                    <label
                        class="flex items-start gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-lg cursor-pointer mb-6 hover:bg-emerald-100 transition-colors">
                        <input type="checkbox" id="f_own_checkout" checked class="checkbox-custom">
                        <div>
                            <strong class="block text-emerald-800 text-sm mb-1">ATIVAR CHECKOUT PRÓPRIO
                                (checkout.html)</strong>
                            <p class="text-xs text-emerald-700 leading-relaxed"><strong>Marcado:</strong> Cliente usa
                                seu checkout interno.<br><strong>Desmarcado:</strong> Vai para link externo configurado.
                            </p>
                        </div>
                    </label>
                    <div id="externalLinkWrap" class="hidden mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Link externo de checkout
                            geral</label>
                        <input type="url" id="f_external_link" class="input-field bg-white" placeholder="https://...">
                    </div>

                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg mb-6">
                        <label class="block text-xs font-bold text-gray-800 mb-2">Puxar Dados por Link (Scraper)</label>
                        <div class="flex gap-2">
                            <input type="url" id="scrapeUrl" class="input-field bg-white flex-1"
                                placeholder="URL do produto...">
                            <button
                                class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg"
                                id="btnScrape">Puxar</button>
                        </div>
                        <div id="scrapeStatus" class="mt-2 text-xs font-semibold hidden rounded p-2"></div>
                    </div>

                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Informações
                        Principais</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Badge/Tag (Ex: Black
                                Friday)</label>
                            <input type="text" id="f_badge"
                                class="input-field bg-pink-50 border-pink-200 focus:border-pink-500 focus:ring-pink-500"
                                placeholder="Tag de destaque">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Título do Produto</label>
                            <input type="text" id="f_title" class="input-field" placeholder="Nome do produto">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Nome da Loja</label>
                                <input type="text" id="f_shop_name" class="input-field" placeholder="Sua Loja">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Avatar da Loja
                                    (URL)</label>
                                <input type="text" id="f_shop_avatar" class="input-field" placeholder="https://...">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Preço Atual (R$)</label>
                                <input type="number" step="0.01" id="f_price" class="input-field" placeholder="49.99">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Preço Antigo (R$)</label>
                                <input type="number" step="0.01" id="f_price_original" class="input-field"
                                    placeholder="99.99">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Texto de Desconto</label>
                            <input type="text" id="f_discount_text" class="input-field" placeholder="Ex: Economize 50%">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Descrição</label>
                            <textarea id="f_description" class="input-field h-32 resize-y"
                                placeholder="Descreva o produto..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="tab-content p-6" data-tab-content="marketing">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Pixels de
                        Rastreamento</h3>
                    <div class="space-y-4 mb-8">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Facebook Pixel ID</label>
                            <input type="text" id="f_pixel_id" class="input-field bg-blue-50 border-blue-200"
                                placeholder="1253561740209342">
                            <span class="block text-[10px] text-gray-500 mt-1">Gera eventos: PageView, ViewContent,
                                AddToCart, InitiateCheckout, Purchase.</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">TikTok Pixel ID</label>
                            <input type="text" id="f_tiktok_pixel" class="input-field" placeholder="CQXXXXXXX...">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Google Tag Manager (GTM)
                                ID</label>
                            <input type="text" id="f_gtm_id" class="input-field" placeholder="GTM-XXXXXXX">
                        </div>
                    </div>

                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Ferramentas de
                        Conversão</h3>
                    <div class="space-y-3">
                        <label
                            class="flex items-start gap-3 p-4 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                            <input type="checkbox" id="f_social_proof" class="checkbox-custom">
                            <div>
                                <strong class="block text-gray-900 text-sm mb-1">ATIVAR PROVA SOCIAL
                                    (Notificações)</strong>
                                <p class="text-xs text-gray-600">Exibe popups como "Maria de SP comprou..." para gerar
                                    urgência.</p>
                            </div>
                        </label>
                        <label
                            class="flex items-start gap-3 p-4 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                            <input type="checkbox" id="f_exit_intent" class="checkbox-custom">
                            <div>
                                <strong class="block text-gray-900 text-sm mb-1">ATIVAR EXIT INTENT (10% OFF ao
                                    sair)</strong>
                                <p class="text-xs text-gray-600">No desktop, oferece desconto no PIX se o cliente tentar
                                    fechar a aba.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="tab-content p-6" data-tab-content="gateway">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Processador de
                        Pagamento</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Gateway Padrão</label>
                            <select id="f_gateway" class="input-field">
                                <option value="nexypay">QuantiumPay</option>
                                <option value="ecompag">Ecompag</option>
                            </select>
                        </div>
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Public Key</label>
                                <input type="text" id="f_gw_public" class="input-field bg-white" placeholder="pk_...">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Secret Key</label>
                                <div class="relative flex items-center">
                                    <input type="password" id="f_gw_secret" class="input-field bg-white pr-10"
                                        placeholder="sk_...">
                                    <button type="button" id="toggleSecret"
                                        class="absolute right-2 p-1.5 text-gray-400 hover:text-gray-900"><i
                                            class="ph ph-eye text-lg"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content p-6" data-tab-content="orderbump">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-2">Cross-sell no
                        Checkout</h3>
                    <p class="text-xs text-gray-500 mb-4">Adicione produtos extras que aparecem na finalização da
                        compra.</p>
                    <div id="bumpList" class="space-y-3"></div>
                    <button id="btnAddBump"
                        class="mt-4 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg w-full hover:bg-gray-800 transition-colors">+
                        Adicionar Bump</button>
                </div>

                <div class="tab-content p-6" data-tab-content="midia">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Galeria de Imagens
                    </h3>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">URLs das Imagens (Uma por
                            linha)</label>
                        <textarea id="f_images" class="input-field h-48 resize-y font-mono text-xs"
                            placeholder="https://.../img1.jpg&#10;https://.../img2.jpg"></textarea>
                        <div id="imgThumbs" class="flex flex-wrap gap-2 mt-3"></div>
                    </div>
                </div>

                <div class="tab-content p-6" data-tab-content="variacoes">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-2">Atributos (Cor,
                        Tamanho, etc)</h3>
                    <p class="text-xs text-gray-500 mb-4">Crie grupos de opções para o cliente escolher.</p>
                    <div id="variationGroupsList" class="space-y-4"></div>
                    <button id="btnAddVarGroup"
                        class="mt-4 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg w-full hover:bg-gray-800 transition-colors">+
                        Adicionar Atributo</button>
                </div>

                <div class="tab-content p-6" data-tab-content="avaliacoes">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Reviews de Clientes
                    </h3>
                    <div id="reviewList" class="space-y-3"></div>
                    <button id="btnAddReview"
                        class="mt-4 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg w-full hover:bg-gray-800 transition-colors">+
                        Adicionar Avaliação</button>
                </div>

                <div class="tab-content p-6" data-tab-content="recomendacoes">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-2">"Você também pode
                        gostar"</h3>
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Desconto Padrão do Bloco
                            (%)</label>
                        <input type="number" id="f_rec_discount" class="input-field bg-white" value="40">
                    </div>
                    <div id="recsList" class="space-y-4"></div>
                    <button id="btnAddRec"
                        class="mt-4 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg w-full hover:bg-gray-800 transition-colors">+
                        Adicionar Recomendação</button>
                </div>

                <div class="tab-content p-6" data-tab-content="rodape">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Empresa e Contato
                    </h3>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-xs font-semibold text-gray-700 mb-1">Razão Social</label><input
                                type="text" id="f_ft_razao" class="input-field"></div>
                        <div><label class="block text-xs font-semibold text-gray-700 mb-1">CNPJ</label><input
                                type="text" id="f_ft_cnpj" class="input-field"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-xs font-semibold text-gray-700 mb-1">E-mail</label><input
                                type="email" id="f_ft_email" class="input-field"></div>
                        <div><label class="block text-xs font-semibold text-gray-700 mb-1">WhatsApp</label><input
                                type="text" id="f_ft_zap" class="input-field"></div>
                    </div>
                    <div class="space-y-4">
                        <div><label class="block text-xs font-semibold text-gray-700 mb-1">Link Políticas</label><input
                                type="url" id="f_ft_pol" class="input-field" placeholder="https://..."></div>
                        <div><label class="block text-xs font-semibold text-gray-700 mb-1">Link Termos</label><input
                                type="url" id="f_ft_term" class="input-field" placeholder="https://..."></div>
                    </div>
                </div>

            </div>
        </div>

        <div class="xl:col-span-5 relative">
            <div class="sticky top-6">

                <button
                    class="w-full mb-4 px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 uppercase tracking-wide"
                    id="btnDownloadZip">
                    <i class="ph ph-download-simple text-xl"></i> Baixar Site Completo (.ZIP)
                </button>

                <div class="bg-gray-100 p-1.5 rounded-xl flex gap-1 mb-4 border border-gray-200" id="previewTabs">
                    <button
                        class="prev-btn flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-900 bg-white shadow-sm transition-all"
                        onclick="switchPreview('produto', this)">Produto</button>
                    <button
                        class="prev-btn flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-900 transition-all"
                        onclick="switchPreview('loja', this)">Loja</button>
                    <button
                        class="prev-btn flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-900 transition-all"
                        onclick="switchPreview('carrinho', this)">Carrinho</button>
                    <button
                        class="prev-btn flex-1 py-1.5 text-xs font-bold rounded-lg text-gray-500 hover:text-gray-900 transition-all"
                        onclick="switchPreview('checkout', this)">Checkout</button>
                </div>

                <div class="iframe-wrapper bg-gray-50 rounded-2xl border border-gray-200">
                    <div id="prev-produto"><iframe id="previewFrame" class="iframe-mobile" src="../template_lp.html"
                            frameborder="0"></iframe></div>
                    <div id="prev-loja" class="hidden"><iframe id="storeFrame" class="iframe-mobile" src="../store.html"
                            frameborder="0"></iframe></div>
                    <div id="prev-carrinho" class="hidden"><iframe id="cartFrame" class="iframe-mobile" src="../cart.html"
                            frameborder="0"></iframe></div>
                    <div id="prev-checkout" class="hidden"><iframe id="checkoutFrame" class="iframe-mobile"
                            src="../checkout.html?v=novo" frameborder="0"></iframe></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fixed inset-0 bg-gray-900/60 z-[1000] hidden items-center justify-center p-4 backdrop-blur-sm"
    id="pasteModal">
    <div class="bg-white rounded-2xl w-full max-w-2xl p-6 shadow-2xl flex flex-col max-h-[90vh]">
        <h2 class="text-xl font-bold text-gray-900 mb-2" id="pasteModalTitle">Importar Produto</h2>
        <p class="text-sm text-gray-600 mb-4">Cole abaixo o código JSON gerado pela Extensão do TikTok Shop. O
            preenchimento tenta ser automático se estiver copiado.</p>
        <textarea class="input-field flex-1 min-h-[250px] font-mono text-xs bg-gray-50 mb-4" id="pasteModalTextarea"
            placeholder='{"_epro_action": "set_main_product"...'></textarea>
        <div class="flex justify-end gap-3 pt-2">
            <button
                class="px-5 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                id="pasteModalCancel">Cancelar</button>
            <button class="px-5 py-2 text-sm font-semibold text-white bg-gray-900 rounded-lg hover:bg-gray-800"
                id="pasteModalConfirm">Processar Importação</button>
        </div>
    </div>
</div>

<div class="fixed inset-0 bg-gray-900/60 z-[1000] hidden items-center justify-center p-4 backdrop-blur-sm"
    id="installModal">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📦 Instalação da Extensão</h2>
        <ol class="list-decimal pl-5 space-y-3 text-sm text-gray-700 mb-6">
            <li>Abra o Chrome e acesse <code
                    class="bg-gray-100 text-pink-600 px-1.5 py-0.5 rounded font-mono">chrome://extensions/</code></li>
            <li>Ative o <strong>Modo do desenvolvedor</strong> no canto superior direito.</li>
            <li>Clique no botão <strong>"Carregar sem compactação"</strong>.</li>
            <li>Selecione a pasta <code
                    class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">tiktok-extractor-extension</code> que você
                baixou.</li>
            <li>Pronto! O ícone da extensão (✓ vermelho) vai aparecer no seu navegador.</li>
        </ol>
        <button class="w-full px-5 py-2.5 text-sm font-bold text-white bg-gray-900 rounded-lg hover:bg-gray-800"
            id="installModalClose">Entendi</button>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script>
    // ── NAVEGAÇÃO DE VIEWS ──
    function toggleView(viewName) {
        document.getElementById('view-lista').classList.toggle('hidden', viewName === 'novo');
        document.getElementById('view-editor').classList.toggle('hidden', viewName === 'lista');
    }

    // ── LÓGICA PRINCIPAL DO EDITOR ──
    (() => {
        'use strict';

        const STORAGE_INDEX_KEY = 'epro_projects_index';
        const STORAGE_PROJECT_PREFIX = 'epro_project_';

        function emptyProject(name) {
            return {
                name: name || 'novo-projeto',
                basico: { badge: '', title: '', price: '', price_original: '', discount_text: '', description: '', images: [], own_checkout: true, external_link: '', shop_name: '', shop_avatar: '' },
                gateway: { provider: 'nexypay', public_key: '', secret_key: '' },
                tracking: { pixel_id: '', tiktok_pixel: '', gtm_id: '', social_proof: false, exit_intent: false },
                orderBumps: [], reviews: [], variationGroups: [], recommendations: { discount: 40, items: [] },
                footer: { razao: '', cnpj: '', email: '', zap: '', pol: '', term: '' }
            };
        }

        let project = emptyProject('novo-projeto');
        const $ = id => document.getElementById(id);

        function setProject(p) {
            project = p;
            window.appProject = project;
        }

        function toast(msg, type = 'info') {
            const el = document.createElement('div');
            el.className = `toast ${type}`;
            el.innerHTML = type === 'success' ? `<i class="ph ph-check-circle text-emerald-500 mr-2"></i> ${msg}` : `<i class="ph ph-warning-circle text-red-500 mr-2"></i> ${msg}`;
            $('toastContainer').appendChild(el);
            setTimeout(() => el.remove(), 4000);
        }
        window.toast = toast;

        // ── TABS DO EDITOR ──
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(t => { t.classList.remove('bg-gray-900', 'text-white'); t.classList.add('text-gray-600', 'bg-white'); });
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.remove('text-gray-600', 'bg-white'); btn.classList.add('bg-gray-900', 'text-white');
                const content = document.querySelector(`[data-tab-content="${btn.dataset.tab}"]`);
                if (content) content.classList.add('active');
            });
        });

        $('toggleSecret')?.addEventListener('click', () => {
            const inp = $('f_gw_secret');
            inp.type = inp.type === 'password' ? 'text' : 'password';
            $('toggleSecret').innerHTML = inp.type === 'password' ? '<i class="ph ph-eye text-lg"></i>' : '<i class="ph ph-eye-slash text-lg"></i>';
        });

        $('f_own_checkout')?.addEventListener('change', () => {
            $('externalLinkWrap').style.display = $('f_own_checkout').checked ? 'none' : 'block';
            syncFormToProject(); pushPreview();
        });

        // ── SINCRONIZAÇÃO AUTOMÁTICA DOS CAMPOS (TWO-WAY BINDING) ──
        const simpleInputs = ['f_badge', 'f_title', 'f_price', 'f_price_original', 'f_discount_text', 'f_description', 'f_external_link', 'f_gateway', 'f_gw_public', 'f_gw_secret', 'f_rec_discount', 'f_ft_razao', 'f_ft_cnpj', 'f_ft_email', 'f_ft_zap', 'f_ft_pol', 'f_ft_term', 'f_shop_name', 'f_shop_avatar', 'f_pixel_id', 'f_tiktok_pixel', 'f_gtm_id'];
        simpleInputs.forEach(id => {
            if ($(id)) $(id).addEventListener('input', () => { syncFormToProject(); pushPreview(); pushStorePreview(); pushChatPreview(); });
        });

        ['f_social_proof', 'f_exit_intent'].forEach(id => {
            if ($(id)) $(id).addEventListener('change', () => { syncFormToProject(); pushPreview(); });
        });

        $('f_images')?.addEventListener('input', () => { syncFormToProject(); renderImageThumbs(); pushPreview(); pushStorePreview(); });

        function syncFormToProject() {
            if (!project.tracking) project.tracking = {};
            project.basico.badge = $('f_badge').value.trim();
            project.basico.title = $('f_title').value.trim();
            project.basico.price = parseFloat($('f_price').value) || 0;
            project.basico.price_original = parseFloat($('f_price_original').value) || 0;
            project.basico.discount_text = $('f_discount_text').value.trim();
            project.basico.description = $('f_description').value.trim();
            project.basico.images = $('f_images').value.split('\n').map(s => s.trim()).filter(Boolean);
            project.basico.own_checkout = $('f_own_checkout').checked;
            project.basico.external_link = $('f_external_link').value.trim();
            project.basico.shop_name = $('f_shop_name')?.value.trim() || '';
            project.basico.shop_avatar = $('f_shop_avatar')?.value.trim() || '';
            project.gateway.provider = $('f_gateway').value;
            project.gateway.public_key = $('f_gw_public').value.trim();
            project.gateway.secret_key = $('f_gw_secret').value.trim();
            project.recommendations.discount = parseFloat($('f_rec_discount').value) || 40;
            project.footer.razao = $('f_ft_razao').value.trim();
            project.footer.cnpj = $('f_ft_cnpj').value.trim();
            project.footer.email = $('f_ft_email').value.trim();
            project.footer.zap = $('f_ft_zap').value.trim();
            project.footer.pol = $('f_ft_pol').value.trim();
            project.footer.term = $('f_ft_term').value.trim();
            project.tracking.pixel_id = $('f_pixel_id')?.value.trim() || '';
            project.tracking.tiktok_pixel = $('f_tiktok_pixel')?.value.trim() || '';
            project.tracking.gtm_id = $('f_gtm_id')?.value.trim() || '';
            project.tracking.social_proof = $('f_social_proof')?.checked || false;
            project.tracking.exit_intent = $('f_exit_intent')?.checked || false;
        }

        function syncProjectToForm() {
            if (!project.variationGroups) project.variationGroups = [];
            if (!project.recommendations) project.recommendations = { discount: 40, items: [] };
            if (!project.footer) project.footer = { razao: '', cnpj: '', email: '', zap: '', pol: '', term: '' };
            if (!project.reviews) project.reviews = [];
            if (!project.orderBumps) project.orderBumps = [];
            if (!project.tracking) project.tracking = {};

            $('f_badge').value = project.basico.badge || '';
            $('f_title').value = project.basico.title || '';
            $('f_price').value = project.basico.price || '';
            $('f_price_original').value = project.basico.price_original || '';
            $('f_discount_text').value = project.basico.discount_text || '';
            $('f_description').value = project.basico.description || '';
            $('f_images').value = (project.basico.images || []).join('\n');
            $('f_own_checkout').checked = project.basico.own_checkout !== false;
            $('f_external_link').value = project.basico.external_link || '';
            $('externalLinkWrap').style.display = $('f_own_checkout').checked ? 'none' : 'block';
            if ($('f_shop_name')) $('f_shop_name').value = project.basico.shop_name || '';
            if ($('f_shop_avatar')) $('f_shop_avatar').value = project.basico.shop_avatar || '';
            $('f_gateway').value = project.gateway.provider || 'nexypay';
            $('f_gw_public').value = project.gateway.public_key || '';
            $('f_gw_secret').value = project.gateway.secret_key || '';
            $('f_rec_discount').value = project.recommendations.discount || 40;
            $('f_ft_razao').value = project.footer.razao || '';
            $('f_ft_cnpj').value = project.footer.cnpj || '';
            $('f_ft_email').value = project.footer.email || '';
            $('f_ft_zap').value = project.footer.zap || '';
            $('f_ft_pol').value = project.footer.pol || '';
            $('f_ft_term').value = project.footer.term || '';
            if ($('f_pixel_id')) $('f_pixel_id').value = project.tracking.pixel_id || '';
            if ($('f_tiktok_pixel')) $('f_tiktok_pixel').value = project.tracking.tiktok_pixel || '';
            if ($('f_gtm_id')) $('f_gtm_id').value = project.tracking.gtm_id || '';
            if ($('f_social_proof')) $('f_social_proof').checked = !!project.tracking.social_proof;
            if ($('f_exit_intent')) $('f_exit_intent').checked = !!project.tracking.exit_intent;

            renderImageThumbs(); renderBumps(); renderReviews(); renderVariationGroups(); renderRecommendations();
            pushPreview(); pushStorePreview(); pushChatPreview();
        }

        function renderImageThumbs() {
            const urls = (project.basico.images || []).slice(0, 6);
            const container = $('imgThumbs');
            container.innerHTML = '';
            urls.forEach(url => {
                const img = document.createElement('img');
                img.src = url; img.className = 'w-12 h-12 object-cover rounded-md border border-gray-200';
                img.onerror = () => img.style.display = 'none';
                container.appendChild(img);
            });
        }

        // ── PUSH PREVIEWS (Envia JSON para IFRAMES via postMessage) ──
        function pushPreview() { $('previewFrame')?.contentWindow?.postMessage({ type: 'epro:update', project }, '*'); }
        function pushCheckoutPreview() { $('checkoutFrame')?.contentWindow?.postMessage({ type: 'epro:update', project }, '*'); }
        function pushStorePreview() { $('storeFrame')?.contentWindow?.postMessage({ type: 'epro:update', project }, '*'); }
        function pushChatPreview() { $('chatFrame')?.contentWindow?.postMessage({ type: 'epro:update', project }, '*'); }

        $('previewFrame')?.addEventListener('load', pushPreview);
        $('checkoutFrame')?.addEventListener('load', pushCheckoutPreview);
        $('storeFrame')?.addEventListener('load', pushStorePreview);
        $('chatFrame')?.addEventListener('load', pushChatPreview);

        window.switchPreview = function (type, el) {
            document.querySelectorAll('.prev-btn').forEach(t => { t.classList.remove('bg-white', 'text-gray-900', 'shadow-sm'); t.classList.add('text-gray-500'); });
            el.classList.remove('text-gray-500'); el.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
            ['produto', 'loja', 'carrinho', 'checkout', 'chat'].forEach(t => {
                const e2 = $('prev-' + t);
                if (e2) e2.style.display = t === type ? '' : 'none';
            });
            if (type === 'loja') pushStorePreview();
            if (type === 'chat') pushChatPreview();
            if (type === 'checkout') { try { localStorage.setItem('epro_lp_project', JSON.stringify(project)); } catch (_) { } pushCheckoutPreview(); }
        };

        // ── SCRAPER ──
        $('btnScrape')?.addEventListener('click', async () => {
            const url = $('scrapeUrl').value.trim();
            const statusEl = $('scrapeStatus');
            const btn = $('btnScrape');
            if (!url) { toast('Informe uma URL para extrair.', 'error'); return; }
            statusEl.style.display = 'block'; statusEl.className = 'mt-2 text-xs font-semibold rounded p-2 bg-blue-50 text-blue-700';
            statusEl.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Buscando dados...';
            btn.disabled = true;
            try {
                const res = await fetch('scraper.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ url }) });
                const rawText = await res.text();
                let result;
                try { result = JSON.parse(rawText); } catch (e) { toast('Erro no scraper/servidor', 'error'); statusEl.style.display = 'none'; return; }
                if (!result.success) { toast('Falha ao extrair.', 'error'); statusEl.style.display = 'none'; return; }
                const d = result.data;
                if (d.title) $('f_title').value = d.title;
                if (d.price) $('f_price').value = d.price;
                if (d.price_original) $('f_price_original').value = d.price_original;
                if (d.description) $('f_description').value = d.description;
                if (d.images && d.images.length) $('f_images').value = d.images.join('\n');
                syncFormToProject();
                if (d.reviews && d.reviews.length) { project.reviews = d.reviews; toast(`${d.reviews.length} avaliações importadas!`, 'success'); }
                syncProjectToForm();
                statusEl.className = 'mt-2 text-xs font-semibold rounded p-2 bg-emerald-50 text-emerald-700'; statusEl.innerHTML = '<i class="ph ph-check-circle"></i> Extraído com sucesso!';
                toast('Importação finalizada', 'success');
            } catch (err) { toast('Erro de rede.', 'error'); } finally { btn.disabled = false; }
        });

        // ── RENDERIZAÇÕES DINÂMICAS (DOM Tailwind) ──
        const esc = v => String(v ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const escHtml = esc;

        function renderBumps() {
            const wrap = $('bumpList'); wrap.innerHTML = '';
            if (!project.orderBumps.length) return wrap.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Nenhum bump cadastrado.</p>';
            project.orderBumps.forEach((bump, i) => {
                const div = document.createElement('div'); div.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4 relative';
                div.innerHTML = `<button class="absolute top-2 right-2 text-red-500 hover:bg-red-50 p-1 rounded transition-colors item-remove"><i class="ph ph-x"></i></button>
                <div class="mb-3"><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Nome do Produto</label><input class="input-field b-name" value="${esc(bump.name)}" placeholder="Capinha extra"></div>
                <div class="grid grid-cols-2 gap-4 mb-3"><div><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Preço (R$)</label><input type="number" step="0.01" class="input-field b-price" value="${bump.price ?? ''}"></div><div><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Original (R$)</label><input type="number" step="0.01" class="input-field b-orig" value="${bump.price_original ?? ''}"></div></div>
                <div class="mb-3"><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Chamada Curta</label><input class="input-field b-desc" value="${esc(bump.description)}" placeholder="Leve junto com desconto"></div>
                <div><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Imagem URL</label><input class="input-field b-img" value="${esc(bump.image)}" placeholder="https://..."></div>`;
                div.querySelector('.item-remove').onclick = () => { project.orderBumps.splice(i, 1); renderBumps(); pushPreview(); pushCheckoutPreview(); };
                div.querySelector('.b-name').oninput = e => { project.orderBumps[i].name = e.target.value; pushPreview(); pushCheckoutPreview(); };
                div.querySelector('.b-price').oninput = e => { project.orderBumps[i].price = parseFloat(e.target.value) || 0; pushPreview(); pushCheckoutPreview(); };
                div.querySelector('.b-orig').oninput = e => { project.orderBumps[i].price_original = parseFloat(e.target.value) || 0; };
                div.querySelector('.b-desc').oninput = e => { project.orderBumps[i].description = e.target.value; };
                div.querySelector('.b-img').oninput = e => { project.orderBumps[i].image = e.target.value; pushPreview(); pushCheckoutPreview(); };
                wrap.appendChild(div);
            });
        }
        $('btnAddBump')?.addEventListener('click', () => { project.orderBumps.push({ name: '', price: 0, price_original: 0, description: '', image: '' }); renderBumps(); });

        function renderReviews() {
            const wrap = $('reviewList'); wrap.innerHTML = '';
            if (!project.reviews.length) return wrap.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Nenhuma avaliação cadastrada.</p>';
            project.reviews.forEach((review, i) => {
                const div = document.createElement('div'); div.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4 relative';
                const starsHtml = [1, 2, 3, 4, 5].map(n => `<i class="ph-fill ph-star cursor-pointer text-xl ${n <= (review.stars ?? 5) ? 'text-yellow-400' : 'text-gray-300'}" data-star="${n}"></i>`).join('');
                div.innerHTML = `<button class="absolute top-2 right-2 text-red-500 hover:bg-red-50 p-1 rounded transition-colors item-remove"><i class="ph ph-x"></i></button>
                <div class="mb-3"><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Cliente</label><input class="input-field r-name" value="${esc(review.name)}" placeholder="Nome"></div>
                <div class="mb-3"><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Nota</label><div class="flex gap-1">${starsHtml}</div></div>
                <div class="mb-3"><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Comentário</label><textarea class="input-field h-16 resize-y r-text">${escHtml(review.text || '')}</textarea></div>
                <div><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Foto URL (Opcional)</label><input class="input-field r-img" value="${esc(review.image || '')}"></div>`;
                div.querySelector('.item-remove').onclick = () => { project.reviews.splice(i, 1); renderReviews(); pushPreview(); };
                div.querySelector('.r-name').oninput = e => { project.reviews[i].name = e.target.value; pushPreview(); };
                div.querySelector('.r-text').oninput = e => { project.reviews[i].text = e.target.value; pushPreview(); };
                div.querySelector('.r-img').oninput = e => { project.reviews[i].image = e.target.value; pushPreview(); };
                div.querySelectorAll('.ph-star').forEach(star => { star.onclick = () => { project.reviews[i].stars = parseInt(star.dataset.star); renderReviews(); pushPreview(); }; });
                wrap.appendChild(div);
            });
        }
        $('btnAddReview')?.addEventListener('click', () => { project.reviews.push({ name: '', stars: 5, text: '', image: '' }); renderReviews(); });

        function renderVariationGroups() {
            const wrap = $('variationGroupsList'); wrap.innerHTML = '';
            if (!project.variationGroups.length) return wrap.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Sem atributos.</p>';
            project.variationGroups.forEach((group, gi) => {
                const groupDiv = document.createElement('div'); groupDiv.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4';
                groupDiv.innerHTML = `
                    <div class="flex gap-2 mb-3">
                        <input type="text" class="input-field flex-1 font-bold" value="${group.name || ''}" placeholder="Nome do Atributo (Ex: Cor)">
                        <button class="px-3 py-2 bg-red-500 text-white rounded-lg text-sm var-group-del"><i class="ph ph-trash"></i></button>
                    </div>
                    <div class="text-[10px] font-bold text-gray-500 uppercase flex gap-2 mb-1 px-1"><span class="flex-[1.5]">Img URL</span><span class="flex-1">Opção</span><span class="flex-1">R$ Extra</span><span class="flex-[1.5]">Link Checkout</span><span class="w-8"></span></div>
                    <div class="opt-container space-y-2"></div>
                    <button class="mt-3 w-full py-1.5 border border-dashed border-gray-300 text-gray-500 rounded-lg text-xs font-bold hover:bg-gray-100 var-add-option">+ Nova Opção</button>`;

                groupDiv.querySelector('.var-group-name')?.addEventListener('input', e => { project.variationGroups[gi].name = e.target.value; pushPreview(); });
                groupDiv.querySelector('.var-group-del').onclick = () => { project.variationGroups.splice(gi, 1); renderVariationGroups(); pushPreview(); };

                const optWrap = groupDiv.querySelector('.opt-container');
                (group.options || []).forEach((opt, oi) => {
                    const row = document.createElement('div'); row.className = 'flex gap-2 items-center bg-white p-1 rounded-lg border border-gray-200';
                    row.innerHTML = `<input type="text" class="input-field !text-xs !p-1.5 flex-[1.5] v-img" value="${opt.image || ''}" placeholder="Img URL"><input type="text" class="input-field !text-xs !p-1.5 flex-1 v-val" value="${opt.value || ''}" placeholder="Preto"><input type="number" class="input-field !text-xs !p-1.5 flex-1 v-price" value="${opt.price || ''}" placeholder="0.00"><input type="text" class="input-field !text-xs !p-1.5 flex-[1.5] v-link" value="${opt.checkout_url || ''}" placeholder="https://..."><button class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-500 rounded v-del"><i class="ph ph-x"></i></button>`;
                    row.querySelector('.v-img').oninput = e => { project.variationGroups[gi].options[oi].image = e.target.value; pushPreview(); };
                    row.querySelector('.v-val').oninput = e => { project.variationGroups[gi].options[oi].value = e.target.value; pushPreview(); };
                    row.querySelector('.v-price').oninput = e => { project.variationGroups[gi].options[oi].price = parseFloat(e.target.value) || 0; pushPreview(); };
                    row.querySelector('.v-link').oninput = e => { project.variationGroups[gi].options[oi].checkout_url = e.target.value; pushPreview(); };
                    row.querySelector('.v-del').onclick = () => { project.variationGroups[gi].options.splice(oi, 1); renderVariationGroups(); pushPreview(); };
                    optWrap.appendChild(row);
                });
                groupDiv.querySelector('.var-add-option').onclick = () => { project.variationGroups[gi].options.push({ value: '', price: 0, image: '', checkout_url: '' }); renderVariationGroups(); };
                wrap.appendChild(groupDiv);
            });
        }
        $('btnAddVarGroup')?.addEventListener('click', () => { project.variationGroups.push({ name: '', options: [] }); renderVariationGroups(); });

        function renderRecommendations() {
            const wrap = $('recsList'); wrap.innerHTML = '';
            if (!project.recommendations.items.length) return wrap.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Nenhuma recomendação criada.</p>';
            project.recommendations.items.forEach((rec, i) => {
                const div = document.createElement('div'); div.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4 relative mb-4';
                let varsHtml = '';
                if (rec.variations && rec.variations.length) {
                    rec.variations.forEach((v, vi) => {
                        varsHtml += `<div class="flex gap-2 mb-2 bg-white p-1 rounded border border-gray-200"><input class="input-field !text-xs !p-1.5 flex-[1.5] rv-img" data-vi="${vi}" value="${esc(v.image)}" placeholder="Img"><input class="input-field !text-xs !p-1.5 flex-1 rv-val" data-vi="${vi}" value="${esc(v.value)}" placeholder="Modelo"><input type="number" class="input-field !text-xs !p-1.5 flex-1 rv-price" data-vi="${vi}" value="${v.price ?? ''}" placeholder="Preço"><button class="bg-red-50 text-red-500 w-8 flex items-center justify-center rounded rv-del" data-vi="${vi}"><i class="ph ph-x"></i></button></div>`;
                    });
                }
                div.innerHTML = `<h4 class="text-xs font-bold text-gray-900 mb-3">Recomendação ${i + 1}</h4><button class="absolute top-2 right-2 text-red-500 hover:bg-red-50 p-1 rounded item-remove"><i class="ph ph-x"></i></button>
                <div class="mb-3"><input class="input-field rec-title" value="${esc(rec.title)}" placeholder="Nome do Produto"></div>
                <div class="grid grid-cols-2 gap-3 mb-3"><div><input type="number" step="0.01" class="input-field rec-price" value="${rec.price ?? ''}" placeholder="Preço Final R$"></div><div><input class="input-field rec-img" value="${esc(rec.image)}" placeholder="URL Imagem Principal"></div></div>
                <div class="mb-4"><input class="input-field rec-link" value="${esc(rec.checkout_url)}" placeholder="Link Checkout (opcional se não usar vars)"></div>
                <div class="border-t border-gray-200 pt-3"><label class="block text-[10px] font-bold text-gray-500 uppercase mb-2">Variações Internas</label><input class="input-field !text-xs mb-2 rec-vname" value="${esc(rec.variation_name)}" placeholder="Nome (Ex: Cor)">
                ${varsHtml}<button class="w-full py-1.5 bg-gray-200 text-gray-700 text-xs font-bold rounded hover:bg-gray-300 transition-colors r-add-var">+ Add Variação Recomendada</button></div>`;

                div.querySelector('.item-remove').onclick = () => { project.recommendations.items.splice(i, 1); renderRecommendations(); pushPreview(); pushStorePreview(); };
                div.querySelector('.rec-title').oninput = e => { project.recommendations.items[i].title = e.target.value; pushPreview(); pushStorePreview(); };
                div.querySelector('.rec-price').oninput = e => { project.recommendations.items[i].price = parseFloat(e.target.value) || 0; pushPreview(); };
                div.querySelector('.rec-img').oninput = e => { project.recommendations.items[i].image = e.target.value; pushPreview(); pushStorePreview(); };
                div.querySelector('.rec-link').oninput = e => { project.recommendations.items[i].checkout_url = e.target.value; pushPreview(); };
                div.querySelector('.rec-vname').oninput = e => { project.recommendations.items[i].variation_name = e.target.value; pushPreview(); };
                div.querySelector('.r-add-var').onclick = () => { if (!project.recommendations.items[i].variations) project.recommendations.items[i].variations = []; project.recommendations.items[i].variations.push({ value: '', price: project.recommendations.items[i].price, image: '', checkout_url: '' }); renderRecommendations(); };
                div.querySelectorAll('.rv-val').forEach(el => el.oninput = e => { project.recommendations.items[i].variations[e.target.dataset.vi].value = e.target.value; pushPreview(); });
                div.querySelectorAll('.rv-price').forEach(el => el.oninput = e => { project.recommendations.items[i].variations[e.target.dataset.vi].price = parseFloat(e.target.value) || 0; pushPreview(); });
                div.querySelectorAll('.rv-img').forEach(el => el.oninput = e => { project.recommendations.items[i].variations[e.target.dataset.vi].image = e.target.value; pushPreview(); });
                div.querySelectorAll('.rv-del').forEach(el => el.onclick = e => { project.recommendations.items[i].variations.splice(e.target.dataset.vi, 1); renderRecommendations(); pushPreview(); });
                wrap.appendChild(div);
            });
        }
        $('btnAddRec')?.addEventListener('click', () => { project.recommendations.items.push({ title: '', price: 0, image: '', checkout_url: '', variation_name: 'Modelo', variations: [] }); renderRecommendations(); });

        // ── PERSISTÊNCIA & ZIP EXPORT ──
        function getIndex() { return JSON.parse(localStorage.getItem(STORAGE_INDEX_KEY) || '[]'); }
        function setIndex(list) { localStorage.setItem(STORAGE_INDEX_KEY, JSON.stringify(list)); }
        function projectKey(name) { return STORAGE_PROJECT_PREFIX + name; }
        function refreshProjectSelect() {
            const select = $('projectSelect'); select.innerHTML = '';
            getIndex().forEach(name => {
                const opt = document.createElement('option'); opt.value = name; opt.textContent = name;
                if (name === project.name) opt.selected = true;
                select.appendChild(opt);
            });
        }

        $('btnSaveProject')?.addEventListener('click', () => {
            syncFormToProject();
            localStorage.setItem(projectKey(project.name), JSON.stringify(project));
            localStorage.setItem('epro_lp_project', JSON.stringify(project));
            const idx = getIndex(); if (!idx.includes(project.name)) { idx.push(project.name); setIndex(idx); }
            refreshProjectSelect(); toast(`Projeto "${project.name}" salvo!`, 'success');
            toggleView('lista'); // Retorna a lista após salvar (opcional, pode remover se quiser manter na edição)
        });

        $('btnNewProject')?.addEventListener('click', () => {
            const name = prompt('Nome do novo projeto:', 'novo-produto');
            if (!name || !name.trim()) return;
            setProject(emptyProject(name.trim())); syncProjectToForm();
            const idx = getIndex(); if (!idx.includes(project.name)) { idx.push(project.name); setIndex(idx); }
            refreshProjectSelect(); toast(`Projeto "${project.name}" criado.`, 'success');
        });

        $('projectSelect')?.addEventListener('change', e => {
            const raw = localStorage.getItem(projectKey(e.target.value));
            if (!raw) return;
            setProject(JSON.parse(raw)); syncProjectToForm(); toast('Projeto carregado.');
        });

        $('btnExportJson')?.addEventListener('click', () => {
            syncFormToProject();
            const blob = new Blob([JSON.stringify(project, null, 2)], { type: 'application/json' });
            const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `${project.name}.json`;
            a.click(); URL.revokeObjectURL(a.href); toast('JSON exportado.', 'success');
        });

        $('btnDownloadZip')?.addEventListener('click', async () => {
            syncFormToProject();
            const btn = $('btnDownloadZip'); const orig = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-spinner animate-spin text-xl"></i> Empacotando Site...'; btn.style.pointerEvents = 'none'; btn.style.opacity = '.8';
            try {
                const res = await fetch('export.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(project) });
                if (res.ok) {
                    const blob = await res.blob(); const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a'); a.href = url; a.download = `epro-${project.name}.zip`;
                    document.body.appendChild(a); a.click(); window.URL.revokeObjectURL(url);
                    toast('ZIP Baixado com sucesso!', 'success');
                } else { toast('Erro no compilador PHP', 'error'); }
            } catch (ex) { toast('Falha na conexão.', 'error'); } finally { btn.innerHTML = orig; btn.style.pointerEvents = 'auto'; btn.style.opacity = '1'; }
        });

        // ── INIT ──
        (function init() {
            const idx = getIndex();
            if (idx.length) {
                const saved = localStorage.getItem(projectKey(idx[0]));
                setProject(saved ? JSON.parse(saved) : emptyProject(idx[0]));
            } else {
                setIndex([project.name]); setProject(project);
                localStorage.setItem(projectKey(project.name), JSON.stringify(project));
            }
            refreshProjectSelect(); syncProjectToForm();
        })();

        // Exposição global para o handler de extensão
        window.appSyncProjectToForm = syncProjectToForm;
        window.appPushPreview = pushPreview; window.appPushStorePreview = pushStorePreview; window.appPushChatPreview = pushChatPreview;

    })();

    // ── MODAL E HANDLER DA EXTENSÃO TIKTOK ──
    (() => {
        let pendingIsRec = false;
        const modal = document.getElementById('pasteModal');
        const modalTextarea = document.getElementById('pasteModalTextarea');
        const installModal = document.getElementById('installModal');

        window.pasteFromExtensionMain = async function () { openPasteModal(false); };
        window.pasteFromExtensionRec = async function () { openPasteModal(true); };

        async function openPasteModal(isRec) {
            pendingIsRec = isRec;
            document.getElementById('pasteModalTitle').textContent = isRec ? 'Importar como Recomendação' : 'Importar Produto Principal';
            modalTextarea.value = '';
            try { if (navigator.clipboard && navigator.clipboard.readText) { const clip = await navigator.clipboard.readText(); if (clip?.trim().startsWith('{')) modalTextarea.value = clip.trim(); } } catch (_) { }
            modal.classList.remove('hidden'); modal.classList.add('flex');
            modalTextarea.focus(); if (modalTextarea.value) modalTextarea.select();
        }

        document.getElementById('pasteModalCancel').onclick = () => { modal.classList.remove('flex'); modal.classList.add('hidden'); };
        document.getElementById('pasteModalConfirm').onclick = () => {
            const json = modalTextarea.value.trim();
            if (!json) { toast('Cole o JSON antes de importar.', 'error'); return; }
            if (importExtensionPayload(json, pendingIsRec)) { modal.classList.remove('flex'); modal.classList.add('hidden'); }
        };

        document.getElementById('btnHowToInstall')?.addEventListener('click', () => { installModal.classList.remove('hidden'); installModal.classList.add('flex'); });
        document.getElementById('installModalClose')?.addEventListener('click', () => { installModal.classList.remove('flex'); installModal.classList.add('hidden'); });

        function importExtensionPayload(jsonStr, forceAsRec) {
            if (!window.appProject) { toast('Erro interno de instância.', 'error'); return false; }
            let payload; try { payload = JSON.parse(jsonStr); } catch (e) { toast('JSON inválido.', 'error'); return false; }
            const action = forceAsRec ? 'add_recommendation' : (payload._epro_action || 'set_main_product');

            if (action === 'add_recommendation') {
                const rec = payload.recommendation || { title: payload.project?.basico?.title || '', price: payload.project?.basico?.price || 0, image: (payload.project?.basico?.images || [])[0] || '', checkout_url: '', variation_name: 'Modelo', variations: [] };
                if (!window.appProject.recommendations) window.appProject.recommendations = { discount: 40, items: [] };
                window.appProject.recommendations.items.push(rec);
                if (window.appSyncProjectToForm) window.appSyncProjectToForm();
                document.querySelector('[data-tab="recomendacoes"]')?.click();
                toggleView('novo');
                toast('Recomendação importada.', 'success'); return true;
            }

            const proj = payload.project;
            if (!proj?.basico) { toast('JSON Estrutura inválida.', 'error'); return false; }
            window.appProject.basico = { ...window.appProject.basico, ...proj.basico };
            if (Array.isArray(proj.reviews)) window.appProject.reviews = proj.reviews;
            if (Array.isArray(proj.variationGroups)) window.appProject.variationGroups = proj.variationGroups;
            if (window.appSyncProjectToForm) window.appSyncProjectToForm();
            toggleView('novo');
            toast(`"${proj.basico.title || 'Produto'}" importado com sucesso!`, 'success'); return true;
        }

        // Bind File Import
        document.getElementById('btnImportJson')?.addEventListener('change', e => {
            const file = e.target.files[0]; if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                if (importExtensionPayload(reader.result, false)) toast('Projeto Carregado do Arquivo', 'success');
            };
            reader.readAsText(file); e.target.value = '';
        });
    })();
</script>

<?php
include '../includes/footer.php';
?>