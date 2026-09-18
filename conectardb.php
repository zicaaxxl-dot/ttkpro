<?php
// conectardb.php - env vars (Docker/Render) with local fallback

$config = array(
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_user' => getenv('DB_USER') ?: 'ttkpro',
    'db_pass' => getenv('DB_PASS') ?: 'ttkpro',
    'db_name' => getenv('DB_NAME') ?: 'ttkpro',
);