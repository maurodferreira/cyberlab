<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

if (current_user() !== null) {
    header('Location: dashboard.php');
    exit;
}

$view = ($_GET['view'] ?? '') === 'register' ? 'register' : 'login';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $view = $action === 'register' ? 'register' : 'login';
    if (!verify_csrf()) {
        http_response_code(403);
        $error = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif ($action === 'register') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $accepted = ($_POST['consent'] ?? '') === 'yes';
        if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
            $error = 'Usuário deve ter de 3 a 32 letras, números ou sublinhado.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $error = 'Informe um e-mail válido.';
        } elseif (strlen($password) < 12 || strlen($password) > 255) {
            $error = 'Use uma senha exclusiva entre 12 e 255 caracteres.';
        } elseif (!$accepted) {
            $error = 'Leia e aceite as condições';
        } else {
            try {
                $stmt = db()->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $userId = (int) db()->lastInsertId();
                log_auth('register', $userId);
                login_session(['id' => $userId, 'username' => $username, 'role' => 'member']);
                header('Location: dashboard.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'Usuário ou e-mail já cadastrado.';
                } else {
                    error_log($exception->getMessage());
                    $error = 'Não foi possível criar a conta. Tente novamente.';
                }
            }
        }
    } elseif ($action === 'login') {
        $identifier = trim((string) ($_POST['identifier'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($identifier === '' || strlen($identifier) > 254 || $password === '') {
            $error = 'Informe suas credenciais de teste.';
        } else {
            try {
                $ip = client_ip();
                $limited = false;
                if ($ip !== null) {
                    $stmt = db()->prepare("SELECT COUNT(*) FROM auth_events WHERE ip_address = ? AND event_type IN ('login_failed', 'rate_limited') AND occurred_at >= NOW() - INTERVAL 15 MINUTE");
                    $stmt->execute([$ip]);
                    $limited = (int) $stmt->fetchColumn() >= 5;
                }
                if ($limited) {
                    log_auth('rate_limited', null);
                    $error = 'Muitas tentativas. Aguarde 15 minutos antes de tentar novamente.';
                } else {
                    $stmt = db()->prepare('SELECT id, username, role, password_hash FROM users WHERE email = ? OR username = ? LIMIT 1');
                    $stmt->execute([$identifier, $identifier]);
                    $user = $stmt->fetch();
                    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
                        log_auth('login_failed', null);
                        $error = 'Usuário ou senha inválidos.';
                    } else {
                        $stmt = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
                        $stmt->execute([(int) $user['id']]);
                        log_auth('login_success', (int) $user['id']);
                        login_session($user);
                        header('Location: dashboard.php');
                        exit;
                    }
                }
            } catch (Throwable $exception) {
                error_log($exception->getMessage());
                $error = 'Falha temporária na autenticação.';
            }
        }
    } else {
        http_response_code(400);
        $error = 'Ação inválida.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>CyberLab | Acesso do laboratório</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="shell auth-shell">
  <section class="intro">
    <div class="eyebrow"><span class="pulse"></span> REDMI 13C · LABORATÓRIO EDUCACIONAL</div>
    <div class="mark">C<span>Y</span>BERLAB</div>
    <h1>Conectividade com<br><em>consciência.</em></h1>
    <p>Portal experimental de autenticação e segurança de redes. Use apenas contas e senhas criadas para os testes.</p>
    <div class="features"><span>◎ Conta de laboratório</span><span>◇ Eventos registrados</span><span>▣ Dados locais</span></div>
  </section>
  <section class="panel" aria-label="Autenticação">
    <div class="tabs">
      <a class="tab <?= $view === 'login' ? 'selected' : '' ?>" href="?view=login">Entrar</a>
      <a class="tab <?= $view === 'register' ? 'selected' : '' ?>" href="?view=register">Criar conta</a>
    </div>
    <h2><?= $view === 'register' ? 'Crie sua conta de teste' : 'Acesse o laboratório' ?></h2>
    <p class="muted"><?= $view === 'register' ? 'Seus registros de teste ficam no MariaDB do aparelho.' : 'Use seu usuário e senha exclusivos do CyberLab.' ?></p>
    <?php if ($view === 'register'): ?>
      <aside class="lab-disclosure" role="note" aria-label="Aviso de privacidade do laboratorio">
        <strong>AVISO DE LABORATÓRIO</strong>
        <p>Este é um experimento educacional. Nome, e-mail de teste, horários, resultado de login e IP local (quando disponível) podem ficar salvos no Redmi 13C. Sua senha será protegida por um hash, não guardada em texto legível. Use somente uma senha fictícia, nunca a de outra conta.</p>
      </aside>
    <?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert" role="alert"><?= h($error) ?></div><?php endif; ?>
    <form action="?view=<?= h($view) ?>" method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="<?= h($view) ?>">
      <?php if ($view === 'register'): ?>
      <label for="username">Nome de usuário</label>
      <input id="username" name="username" required minlength="3" maxlength="32" pattern="[A-Za-z0-9_]+" placeholder="ex.: aluno_lab01">
      <label for="email">E-mail</label>
      <input id="email" type="email" name="email" maxlength="254" required placeholder="aluno@example.test">
      <?php else: ?>
      <label for="identifier">Usuário ou e-mail</label>
      <input id="identifier" name="identifier" required maxlength="254" placeholder="Seu usuário de laboratório">
      <?php endif; ?>
      <label for="password">Senha </label>
      <input id="password" type="password" name="password" required <?= $view === 'register' ? 'minlength="12" maxlength="255"' : '' ?> autocomplete="<?= $view === 'register' ? 'new-password' : 'current-password' ?>">
      <?php if ($view === 'register'): ?>
        <label class="consent"><input type="checkbox" name="consent" value="yes" required><span>Li o aviso e concordo com o registro dos meus dados de teste neste laboratório.</span></label>
      <?php endif; ?>
      <button class="primary" type="submit"><?= $view === 'register' ? 'Criar conta de teste' : 'Entrar no CyberLab' ?><span>→</span></button>
    </form>
  </section>
</main>
</body></html>