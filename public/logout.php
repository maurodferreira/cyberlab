<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    http_response_code(403);
    exit('Solicitação inválida.');
}
$user = current_user();
if ($user !== null) {
    try { log_auth('logout', (int) $user['id']); } catch (Throwable $e) { error_log($e->getMessage()); }
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: index.php');
exit;
