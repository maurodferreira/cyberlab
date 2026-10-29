<?php
// Copie este arquivo para config/local.php e preencha SOMENTE no Redmi.
// Nunca envie config/local.php ao GitHub.
return [
    'db_dsn' => 'mysql:unix_socket=/data/data/com.termux/files/usr/var/run/mysqld.sock;dbname=cyberlab;charset=utf8mb4',
    'db_user' => 'cyberlab_app',
    'db_password' => 'SUBSTITUA_PELA_SENHA_LOCAL',
];
