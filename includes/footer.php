<?php
// includes/footer.php
?>
</main>
</div>
<script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggle-sidebar');
    const menuTexts = document.querySelectorAll('.menu-text');
    const sidebarTitle = document.getElementById('sidebar-title');
    let sidebarOpen = true;

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebarOpen = !sidebarOpen;
            if (sidebarOpen) {
                sidebar.classList.remove('w-16');
                sidebar.classList.add('w-64');
                sidebarTitle.style.display = 'block';
                menuTexts.forEach(txt => txt.style.display = 'inline');
            } else {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-16');
                sidebarTitle.style.display = 'none';
                menuTexts.forEach(txt => txt.style.display = 'none');
            }
        });
    }

    // --- LÓGICA DO TEMA (DARK/LIGHT MODE) ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    // Função para atualizar o ícone (Sol/Lua)
    function updateThemeIcon() {
        if (!themeIcon) return;
        if (document.documentElement.classList.contains('dark')) {
            themeIcon.classList.remove('ph-moon');
            themeIcon.classList.add('ph-sun');
        } else {
            themeIcon.classList.remove('ph-sun');
            themeIcon.classList.add('ph-moon');
        }
    }

    // Chama a função ao carregar a página para o ícone começar certo
    updateThemeIcon();

    // Evento de clique no botão
    themeToggleBtn?.addEventListener('click', () => {
        // Alterna a classe 'dark' no <html>
        document.documentElement.classList.toggle('dark');

        // Salva a nova preferência
        if (document.documentElement.classList.contains('dark')) {
            localStorage.theme = 'dark';
        } else {
            localStorage.theme = 'light';
        }

        // Atualiza o ícone
        updateThemeIcon();
    });
</script>
</body>

</html>