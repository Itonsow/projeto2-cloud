<?php

require_once __DIR__ . '/db.php';

// Busca os scripts salvos, do mais recente para o mais antigo.
function listar_scripts(): array
{
    $sql = 'SELECT id, nome, descricao, conteudo, criado_em
            FROM scripts
            ORDER BY criado_em DESC, id DESC';

    return db()->query($sql)->fetchAll();
}


function salvar_script(
    string $nome,
    string $descricao,
    string $conteudo
): int {
    $sql = 'INSERT INTO scripts (nome, descricao, conteudo)
            VALUES (:nome, :descricao, :conteudo)';

    $stmt = db()->prepare($sql);

    $stmt->execute([
        'nome' => $nome,
        'descricao' => $descricao === '' ? null : $descricao,
        'conteudo' => $conteudo,
    ]);

    return (int) db()->lastInsertId();
}

// Retorna o script encontrado ou null quando o ID não existe.
function buscar_script(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, nome, descricao, conteudo, criado_em
         FROM scripts
         WHERE id = :id'
    );

    $stmt->execute(['id' => $id]);
    $script = $stmt->fetch();

    return $script === false ? null : $script;
}

// Retorna true se um registro foi removido.
function remover_script(int $id): bool
{
    $stmt = db()->prepare('DELETE FROM scripts WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}