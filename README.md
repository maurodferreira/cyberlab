# CyberLab — Portal de laboratório no Redmi 13C

Aplicação educacional PHP + MariaDB para **contas próprias de teste**, histórico de login e painel administrativo. Repositório de código; **dados dos participantes nunca devem ser publicados aqui**.

> **Importante:** esta versão **não cria nem controla o hotspot**, não faz redirecionamento automático e **não libera a internet**. Ela apenas autentica o acesso ao portal web. Somente em uma rede própria e autorizada. Não use senhas reais, credenciais de outras plataformas ou dados pessoais de terceiros em conexões HTTP.

## Estrutura

- `public/index.php` — login e cadastro; único diretório que deve ser servido pelo Apache.
- `public/dashboard.php` — página acessível após autenticação.
- `public/admin.php` — lista de contas/eventos e CSV (papel `admin`).
- `src/bootstrap.php` — conexão MariaDB, sessão, CSRF e funções compartilhadas.
- `database/schema.sql` — estrutura das tabelas e campos armazenados.
- `config/config.example.php` — modelo sem dados sensíveis.
- `config/local.php` — configuração privada **não versionada**.

## O que fica gravado

| Tabela | Campos | Observação |
|---|---|---|
| `users` | `id`, `username`, `email`, `password_hash`, `role`, `created_at`, `last_login_at` | Senha apenas em hash, nunca recuperável em texto puro |
| `auth_events` | `id`, `user_id`, `event_type`, `ip_address`, `occurred_at` | Eventos e IP local, quando disponível; não armazena senhas |

Eventos de falha não guardam e-mail digitado nem senha. Existem proteção CSRF, sessões HTTP-only, consultas preparadas e limite básico de tentativas. O limite e os cookies exigem revisão antes de publicar fora da rede de laboratório.

## Preparar no Windows (VS Code)

```powershell
git clone https://github.com/maurodferreira/cyberlab.git
cd cyberlab
code .
```

Para trabalhar em uma branch: `git switch feat/auth-mvp` depois de buscar branches com `git fetch origin`.

## Instalar no Termux (Redmi 13C)

O Termux já tem `git`, Apache, PHP, `php-apache` e MariaDB instalados. O usuário `cyberlab_app` precisa ter `SELECT, INSERT, UPDATE, DELETE` em `cyberlab.*`, como configurado nas aulas.

```bash
cd "$HOME"
git clone https://github.com/maurodferreira/cyberlab.git
cd cyberlab
cp config/config.example.php config/local.php
nano config/local.php
```

Na configuração privada, informe a senha do usuário `cyberlab_app`; mantenha o socket já validado `/data/data/com.termux/files/usr/var/run/mysqld.sock`. **Não envie o arquivo ao GitHub**.

Como `cyberlab_app` não tem permissões para criar tabelas, crie-as como administrador do MariaDB:

```bash
mariadb -u root < database/schema.sql
```

Para testar inicialmente sem tocar no Apache, abra outro terminal Termux:

```bash
php -S 127.0.0.1:8081 -t public
```

Acesse `http://127.0.0.1:8081` no navegador do próprio Redmi. Use apenas contas fictícias. O servidor de desenvolvimento do PHP não deve ser exposto a uma rede pública.

### Servir pelo Apache existente

Após validar localmente, aponte o **DocumentRoot** do Apache para o caminho absoluto de `public` e ajuste o bloco `<Directory>` correspondente. **Nunca** aponte o DocumentRoot para a raiz do repositório: ela contém `src/`, `config/` e a estrutura do banco. Valide com `httpd -t` antes de reiniciar. O Apache com PHP já foi configurado anteriormente no Termux.

## Criar administrador do laboratório

Após cadastrar uma conta de teste pelo navegador, execute como administrador do MariaDB (substitua `aluno_admin` pelo nome **exato** do usuário cadastrado):

```sql
UPDATE cyberlab.users SET role = 'admin' WHERE username = 'aluno_admin';
```

Saia e entre novamente no portal para atualizar a sessão. **Cadastro público nunca concede acesso administrativo.**

## Como visualizar os registros

No painel admin: `admin.php` (contas e últimos 100 eventos, com exportação CSV dos eventos exibidos). Ou via MariaDB:

```sql
SELECT id, username, email, created_at, last_login_at FROM cyberlab.users ORDER BY id DESC;
SELECT id, user_id, event_type, ip_address, occurred_at FROM cyberlab.auth_events ORDER BY id DESC LIMIT 50;
```

Para alterar quais dados são guardados, edite `database/schema.sql` para novas instalações e crie uma migração `database/migrations/` para bancos existentes. Atualize também o código PHP que grava e lê os campos. Não edite arquivos SQL esperando que o banco existente mude automaticamente.

## Cuidados de segurança

- Exibir aviso claro de coleta e usar apenas dados fictícios dos participantes do laboratório.
- Nunca armazenar senhas em texto puro ou registrar corpo de formulários de login.
- Acesso via HTTP apenas para demonstração **local** com credenciais descartáveis; implantar HTTPS antes de qualquer uso real com terceiros.
- Limitar acesso à área administrativa; restringir acesso aos registros e CSV.
- Excluir cadastros e logs ao encerrar o experimento ou após um período curto previamente informado.
- Para apagar eventos de teste com mais de 7 dias (depois de acordar a política):

```sql
DELETE FROM cyberlab.auth_events WHERE occurred_at < NOW() - INTERVAL 7 DAY;
```

## Próximo módulo

Integrar um **controlador real de portal cativo** ao hotspot de laboratório. Atualmente o HyperOS/Termux hospeda a aplicação, mas não faz redirecionamento obrigatório nem controla internet. Essas funcionalidades exigem infraestrutura de rede compatível e testes autorizados.
