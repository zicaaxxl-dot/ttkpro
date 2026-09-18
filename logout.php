<?php
require_once __DIR__ . '/includes/config.php';
startAdminSession();
$_SESSION = [];
session_destroy();
header('Location: login');
exit;