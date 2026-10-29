<?php
declare(strict_types=1);

// Este arquivo fica FORA do diretório público do Apache.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Sao_Paulo');

$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('CYBERLAB_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function app_config(): array
{
    $path = dirname(__DIR__) . '/config/local.php';
    if (!is_file($path)) {
        http_response_code(503);
        exit('CyberLab ainda não configurado. Consulte o README.');
    }
    $config = require $path;
    if (!is_array($config)) {
        http_response_code(503);
        exit('Configuração local inválida.');
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = app_config();
    $pdo = new PDO($cfg['db_dsn'], $cfg['db_user'], $cfg['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function verify_csrf(): bool
{
    return isset($_POST['csrf']) && is_string($_POST['csrf'])
        && hash_equals(csrf_token(), $_POST['csrf']);
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: index.php');
        exit;
    }
    return $user;
}

function client_ip(): ?string
{
    // Não confiar em X-Forwarded-For enviado pelo visitante.
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}

function log_auth(string $event, ?int $userId): void
{
    $stmt = db()->prepare('INSERT INTO auth_events (user_id, event_type, ip_address) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $event, client_ip()]);
}

function login_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
