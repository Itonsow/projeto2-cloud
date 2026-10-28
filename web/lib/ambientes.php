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

// Recebe dados já validados e cria o registro no banco.
function criar_ambiente(array $dados): int
{
    $sql = 'INSERT INTO ambientes (
                nome,
                cpu_percentual,
                memoria_mb,
                io_leitura_mbps,
                io_escrita_mbps,
                isolar_rede,
                tipo,
                script_id,
                conteudo_execucao
            ) VALUES (
                :nome,
                :cpu_percentual,
                :memoria_mb,
                :io_leitura_mbps,
                :io_escrita_mbps,
                :isolar_rede,
                :tipo,
                :script_id,
                :conteudo_execucao
            )';

    $stmt = db()->prepare($sql);

    $stmt->execute([
        'nome' => $dados['nome'],
        'cpu_percentual' => $dados['cpu_percentual'],
        'memoria_mb' => $dados['memoria_mb'],
        'io_leitura_mbps' => $dados['io_leitura_mbps'] ?? null,
        'io_escrita_mbps' => $dados['io_escrita_mbps'] ?? null,
        'isolar_rede' => $dados['isolar_rede'] ?? 0,
        'tipo' => $dados['tipo'],
        'script_id' => $dados['script_id'] ?? null,
        'conteudo_execucao' => $dados['conteudo_execucao'],
    ]);

    return (int) db()->lastInsertId();
}

// Atualiza somente os campos recebidos.
function atualizar_ambiente(int $id, array $dados): void
{
    $permitidos = [
        'nome',
        'cpu_percentual',
        'memoria_mb',
        'io_leitura_mbps',
        'io_escrita_mbps',
        'isolar_rede',
        'tipo',
        'script_id',
        'conteudo_execucao',
        'status',
        'pid',
        'exit_code',
        'motivo',
    ];

    if ($dados === []) {
        throw new InvalidArgumentException(
            'Informe pelo menos um campo para atualizar.'
        );
    }

    $campos = [];
    $parametros = ['id' => $id];

    foreach ($dados as $campo => $valor) {
        if (!in_array($campo, $permitidos, true)) {
            throw new InvalidArgumentException(
                'Campo não permitido: ' . $campo
            );
        }

        $campos[] = "`$campo` = :$campo";
        $parametros[$campo] = $valor;
    }

    $sql = 'UPDATE ambientes SET '
        . implode(', ', $campos)
        . ' WHERE id = :id';

    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);
}

// Remove somente o registro no MySQL.
function remover_ambiente(int $id): bool
{
    $stmt = db()->prepare(
        'DELETE FROM ambientes WHERE id = :id'
    );

    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}