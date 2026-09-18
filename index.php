<?php
/**
 * index.php
 * Redireciona os visitantes da raiz (/) para a página de login limpa (/login)
 */

header("Location: /login", true, 302);
exit;