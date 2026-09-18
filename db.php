<?php
/**
 * db.php
 * Conexão PDO única (singleton). Usa as credenciais do seu conectardb.php
 * (o array $config que você já tem: db_host, db_user, db_pass, db_name).
 */

require_once __DIR__ . '/conectardb.php'; // fornece $config

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        global $config;

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['db_host'],
            $config['db_name']
        );

        try {
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Nunca vaze host/senha no erro em produção.
            error_log('[ttkpro] Falha ao conectar no banco: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']));
        }
    }

    return $pdo;
}