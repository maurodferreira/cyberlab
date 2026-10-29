<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$user = require_login();
if ($user['role'] !== 'admin') { http_response_code(403); exit('Acesso reservado ao administrador.'); }
$users = db()->query('SELECT id, username, email, role, created_at, last_login_at FROM users ORDER BY id DESC LIMIT 100')->fetchAll();
$events = db()->query('SELECT e.id, e.event_type, e.ip_address, e.occurred_at, u.username FROM auth_events e LEFT JOIN users u ON u.id = e.user_id ORDER BY e.id DESC LIMIT 100')->fetchAll();
if (($_GET['export'] ?? '') === 'events') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cyberlab-eventos.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id', 'tipo', 'usuario', 'ip_local', 'horario']);
    foreach ($events as $e) { fputcsv($out, [$e['id'], $e['event_type'], $e['username'], $e['ip_address'], $e['occurred_at']]); }
    fclose($out);
    exit;
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CyberLab | Administração</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="shell dashboard-shell">
<header class="topbar"><div class="brand">C<span>Y</span>BERLAB / ADMIN</div><a class="text-button" href="dashboard.php">Voltar ao painel</a></header>
<div class="eyebrow"><span class="pulse"></span> ACESSO RESTRITO</div><h1>Registros do laboratório</h1><p class="muted">Até 100 registros recentes por tabela. Somente dados de contas de teste.</p>
<div class="grid-cards"><article class="metric"><span>CONTAS EXIBIDAS</span><strong><?= count($users) ?></strong></article><article class="metric"><span>EVENTOS EXIBIDOS</span><strong><?= count($events) ?></strong></article><article class="metric"><span>EXPORTAR</span><strong><a class="secondary-link" href="?export=events">Baixar CSV</a></strong></article></div>
<section class="table-card"><h2>Usuários</h2><div class="table-scroll"><table><thead><tr><th>ID</th><th>Usuário</th><th>E-mail</th><th>Papel</th><th>Cadastro</th><th>Último login</th></tr></thead><tbody><?php foreach ($users as $u): ?><tr><td><?= (int) $u['id'] ?></td><td><?= h($u['username']) ?></td><td><?= h($u['email']) ?></td><td><?= h($u['role']) ?></td><td><?= h((string)$u['created_at']) ?></td><td><?= h((string)($u['last_login_at'] ?? '-')) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="table-card"><h2>Eventos de autenticação</h2><div class="table-scroll"><table><thead><tr><th>ID</th><th>Evento</th><th>Usuário</th><th>IP local</th><th>Horário</th></tr></thead><tbody><?php foreach ($events as $e): ?><tr><td><?= (int) $e['id'] ?></td><td><?= h($e['event_type']) ?></td><td><?= h((string)($e['username'] ?? '-')) ?></td><td><?= h((string)($e['ip_address'] ?? '-')) ?></td><td><?= h((string)$e['occurred_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<p class="notice">Dados pessoais de laboratório. Exporte apenas quando necessário, proteja o CSV e exclua os registros ao encerrar o experimento.</p>
</main></body></html>
