#!/usr/bin/env bash
set -euo pipefail

# Localiza a pasta principal do projeto.
PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"

# O provisionamento precisa de permissões administrativas.
if [[ "$EUID" -ne 0 ]]; then
    echo "Erro: execute este script com sudo." >&2
    exit 1
fi

echo "Iniciando o MySQL..."
systemctl enable --now mysql

echo "Configurando banco e usuário..."

php -- "$PROJECT_DIR" <<'PHP'
<?php

$projectDir = $argv[1];
$config = require $projectDir . '/web/config.php';
$db = $config['db'];

// Acesso administrativo pelo socket local do MySQL.
$pdo = new PDO(
    'mysql:unix_socket=/var/run/mysqld/mysqld.sock;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Valida o nome antes de utilizá-lo como identificador SQL.
if (!preg_match('/^[a-zA-Z0-9_]+$/D', $db['name'])) {
    throw new RuntimeException('Nome de banco inválido.');
}

$database = '`' . $db['name'] . '`';
$account = $pdo->quote($db['user']) . '@' . $pdo->quote($db['host']);
$password = $pdo->quote($db['password']);

$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS $database
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE USER IF NOT EXISTS $account IDENTIFIED BY $password"
);

// Atualiza a senha caso o usuário já exista.
$pdo->exec(
    "ALTER USER $account IDENTIFIED BY $password"
);

// Permissões necessárias para o backend manipular os registros.
$pdo->exec(
    "GRANT SELECT, INSERT, UPDATE, DELETE
     ON $database.* TO $account"
);
PHP

echo "Criando as tabelas..."
mysql --database=projeto2 < "$PROJECT_DIR/db/schema.sql"

echo "Banco de dados configurado com sucesso."