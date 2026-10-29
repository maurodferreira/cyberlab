<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$user = require_login();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CyberLab | Painel</title><link rel="stylesheet" href="assets/style.css"></head>
<body><main class="shell dashboard-shell">
<header class="topbar"><div class="brand">C<span>Y</span>BERLAB</div><form action="logout.php" method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="text-button" type="submit">Sair da conta ↗</button></form></header>
<section class="hero-card"><div class="eyebrow"><span class="pulse"></span> SESSÃO AUTENTICADA</div><h1>Bem-vindo, <?= h((string) $user['username']) ?>.</h1><p>Seu cadastro de teste está ativo. O acesso à internet ainda não é controlado por esta aplicação.</p><div class="chips"><span>✓ Login confirmado</span><span>⚠ Senha em texto puro (didático)</span><span>✓ Eventos registrados</span></div></section>
<section class="grid-cards"><article class="metric"><span>STATUS</span><strong>Portal online</strong><p>Aplicação PHP no Android</p></article><article class="metric"><span>SESSÃO</span><strong>Autenticado</strong><p>Cookie HTTP-only de sessão</p></article><article class="metric"><span>REDE</span><strong>Em estudo</strong><p>Liberação de Wi-Fi ainda não integrada</p></article></section>
<?php if ($user['role'] === 'admin'): ?><p><a class="secondary-link" href="admin.php">Abrir painel administrativo →</a></p><?php endif; ?>
<p class="notice">Laboratório demonstrativo. Não utilize senhas de contas pessoais.</p>
</main></body></html>
