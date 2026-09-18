<?php
// dashboard/index.php
$titulo = 'Visão Geral - ttkpro';
$menuAtivo = 'dashboard';
include '../includes/header.php';

// Aqui entraria sua query real do banco de dados futuramente
$dadosOverview = [
    'vendas_periodo' => '332,17',
    'aprovadas' => '5,00',
    'pendentes' => '4',
    'ticket_medio' => '5,00',
    'vendas_hoje' => '0,00',
    'pedidos_hoje' => '0',
    'ultimos_7_dias' => '5,00',
    'produtos_qtd' => '2',
    'conversao' => '20.0%'
];

/* * NOTA: O header.php já abriu as tags <html>, <body>, carregou o Tailwind, 
 * renderizou a Sidebar, o Topbar e abriu a tag <main class="flex-1 overflow-y-auto p-8">.
 * Portanto, começamos direto no conteúdo!
 */
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Visão Geral</h1>
    <div class="flex items-center border border-gray-200 rounded-full p-1 bg-white text-sm" id="date-filters">
        <button class="filter-btn px-4 py-1.5 rounded-full bg-gray-900 text-white font-medium transition-colors"
            data-period="hoje">Hoje</button>
        <button
            class="filter-btn px-4 py-1.5 rounded-full text-gray-600 hover:bg-gray-100 font-medium transition-colors"
            data-period="7dias">7 dias</button>
        <button
            class="filter-btn px-4 py-1.5 rounded-full text-gray-600 hover:bg-gray-100 font-medium transition-colors"
            data-period="30dias">30 dias</button>
        <button
            class="filter-btn px-4 py-1.5 rounded-full text-gray-600 hover:bg-gray-100 font-medium transition-colors"
            data-period="tudo">Tudo</button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm text-gray-500">Vendas (período)</span>
            <i class="ph ph-currency-dollar text-gray-400 text-lg"></i>
        </div>
        <div class="text-2xl font-bold transition-all" id="val-vendas">R$
            <?php echo $dadosOverview['vendas_periodo']; ?>
        </div>
    </div>

    <div class="card p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm text-gray-500">Aprovadas</span>
            <i class="ph ph-check-circle text-brand-green text-lg"></i>
        </div>
        <div class="text-2xl font-bold transition-all" id="val-aprovadas">R$ <?php echo $dadosOverview['aprovadas']; ?>
        </div>
    </div>

    <div class="card p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm text-gray-500">Pendentes</span>
            <i class="ph ph-clock text-brand-orange text-lg"></i>
        </div>
        <div class="text-2xl font-bold transition-all" id="val-pendentes"><?php echo $dadosOverview['pendentes']; ?>
        </div>
    </div>

    <div class="card p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm text-gray-500">Ticket médio</span>
            <i class="ph ph-receipt text-gray-400 text-lg"></i>
        </div>
        <div class="text-2xl font-bold transition-all" id="val-ticket">R$ <?php echo $dadosOverview['ticket_medio']; ?>
        </div>
    </div>

    <div class="card p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm text-gray-500">Vendas hoje</span>
            <i class="ph ph-calendar-blank text-brand-green text-lg"></i>
        </div>
        <div class="text-xl font-bold transition-all" id="val-vendas-hoje">R$
            <?php echo $dadosOverview['vendas_hoje']; ?> <span class="text-base font-medium text-gray-900"
                id="val-pedidos-hoje">· <?php echo $dadosOverview['pedidos_hoje']; ?> pedidos</span>
        </div>
    </div>

    <div class="card p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm text-gray-500">Últimos 7 dias</span>
            <i class="ph ph-calendar-blank text-brand-green text-lg"></i>
        </div>
        <div class="text-2xl font-bold transition-all" id="val-ultimos">R$
            <?php echo $dadosOverview['ultimos_7_dias']; ?>
        </div>
    </div>

    <div class="card p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm text-gray-500">Produtos</span>
            <i class="ph ph-cube text-gray-900 text-lg"></i>
        </div>
        <div class="text-2xl font-bold transition-all" id="val-produtos"><?php echo $dadosOverview['produtos_qtd']; ?>
        </div>
    </div>

    <div class="card p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
            <span class="text-sm text-gray-500">Conversão</span>
            <i class="ph ph-trend-up text-brand-orange text-lg"></i>
        </div>
        <div class="text-2xl font-bold transition-all" id="val-conversao"><?php echo $dadosOverview['conversao']; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6 h-80">
    <div class="card p-5 flex flex-col">
        <h3 class="text-sm font-semibold mb-4">Vendas aprovadas por dia</h3>
        <div class="flex-1 relative w-full h-full">
            <canvas id="lineChart"></canvas>
        </div>
    </div>

    <div class="card p-5 flex flex-col">
        <h3 class="text-sm font-semibold mb-4">Pedidos por status</h3>
        <div class="flex-1 relative w-full h-full flex items-center justify-center">
            <canvas id="pieChart"></canvas>
        </div>
    </div>
</div>

<div class="card p-5 h-64">
    <h3 class="text-sm font-semibold mb-4">Top 5 produtos vendidos</h3>
    <div class="mt-4">
        <div class="flex justify-between text-sm mb-1">
            <span class="font-medium">Produto Exemplo A</span>
            <span class="text-gray-500">2 vendas</span>
        </div>
        <div class="w-full bg-gray-200 rounded-sm h-8 relative">
            <div class="bg-brand-green h-8 rounded-sm transition-all duration-500" style="width: 100%"
                id="bar-produto1"></div>
        </div>
    </div>
</div>

<script>
    // --- Configuração Inicial dos Gráficos (Chart.js já foi carregado no header.php) ---
    const ctxLine = document.getElementById('lineChart').getContext('2d');
    let lineChart = new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: ['16/04', '17/04', '18/04', '19/04', '20/04', '21/04', '22/04'],
            datasets: [{
                label: 'Vendas',
                data: [0, 0, 0, 0, 0, 5, 0],
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#22c55e',
                pointBorderColor: '#fff',
                pointRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 8,
                    ticks: { stepSize: 2, color: '#9ca3af', font: { size: 11 } },
                    border: { display: false },
                    grid: { color: '#e5e7eb', borderDash: [5, 5], drawBorder: false }
                },
                x: {
                    ticks: { color: '#9ca3af', font: { size: 11 } },
                    border: { display: false },
                    grid: { display: false }
                }
            }
        }
    });

    const ctxPie = document.getElementById('pieChart').getContext('2d');
    let pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Aprovados', 'Pendentes'],
            datasets: [{
                data: [1, 4],
                backgroundColor: ['#22c55e', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: 20 },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 10, color: '#6b7280', font: { size: 12 } }
                }
            }
        }
    });

    // --- Lógica dos Filtros de Data e Simulação de Dados ---
    const filterBtns = document.querySelectorAll('.filter-btn');

    const dataSimulation = {
        'hoje': { vendas: '0,00', aprovadas: '0,00', pendentes: '0', ticket: '0,00', hoje: '0,00', pedidosHoje: '0', ultimos: '5,00', conversao: '0.0%', chartLine: [0, 0, 0, 0, 0, 0, 0], chartPie: [0, 0] },
        '7dias': { vendas: '332,17', aprovadas: '5,00', pendentes: '4', ticket: '5,00', hoje: '0,00', pedidosHoje: '0', ultimos: '5,00', conversao: '20.0%', chartLine: [0, 0, 0, 0, 0, 5, 0], chartPie: [1, 4] },
        '30dias': { vendas: '1.450,80', aprovadas: '1.200,00', pendentes: '12', ticket: '120,00', hoje: '0,00', pedidosHoje: '0', ultimos: '332,17', conversao: '35.5%', chartLine: [2, 5, 1, 8, 4, 12, 5], chartPie: [15, 12] },
        'tudo': { vendas: '15.890,50', aprovadas: '14.500,00', pendentes: '8', ticket: '145,00', hoje: '0,00', pedidosHoje: '0', ultimos: '332,17', conversao: '42.1%', chartLine: [10, 25, 15, 30, 20, 45, 35], chartPie: [145, 8] }
    };

    filterBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            filterBtns.forEach(b => {
                b.classList.remove('bg-gray-900', 'text-white');
                b.classList.add('text-gray-600', 'hover:bg-gray-100');
            });
            const clickedBtn = e.currentTarget;
            clickedBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
            clickedBtn.classList.add('bg-gray-900', 'text-white');

            const period = clickedBtn.getAttribute('data-period');
            const newData = dataSimulation[period];

            document.getElementById('val-vendas').innerText = 'R$ ' + newData.vendas;
            document.getElementById('val-aprovadas').innerText = 'R$ ' + newData.aprovadas;
            document.getElementById('val-pendentes').innerText = newData.pendentes;
            document.getElementById('val-ticket').innerText = 'R$ ' + newData.ticket;
            document.getElementById('val-vendas-hoje').innerHTML = `R$ ${newData.hoje} <span class="text-base font-medium text-gray-900" id="val-pedidos-hoje">· ${newData.pedidosHoje} pedidos</span>`;
            document.getElementById('val-ultimos').innerText = 'R$ ' + newData.ultimos;
            document.getElementById('val-conversao').innerText = newData.conversao;

            const maxLineData = Math.max(...newData.chartLine);
            lineChart.options.scales.y.max = maxLineData > 0 ? (maxLineData + Math.ceil(maxLineData * 0.2)) : 8;
            lineChart.data.datasets[0].data = newData.chartLine;
            lineChart.update();

            pieChart.data.datasets[0].data = newData.chartPie;
            pieChart.update();

            const bar = document.getElementById('bar-produto1');
            bar.style.width = '0%';
            setTimeout(() => { bar.style.width = (period === 'hoje' ? '0%' : '100%'); }, 100);
        });
    });
</script>

<?php
include '../includes/footer.php';
?>