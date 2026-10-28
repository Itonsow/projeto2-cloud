<?php

require_once __DIR__ . '/db.php';

// Lista os ambientes, do mais recente para o mais antigo.
function listar_ambientes(): array
{
    $sql = 'SELECT *
            FROM ambientes
            ORDER BY criado_em DESC, id DESC';

    return db()->query($sql)->fetchAll();
}

// Busca um ambiente pelo ID. Retorna null se não existir.
function buscar_ambiente(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM ambientes WHERE id = :id'
    );

    $stmt->execute(['id' => $id]);
    $ambiente = $stmt->fetch();

    return $ambiente === false ? null : $ambiente;
}