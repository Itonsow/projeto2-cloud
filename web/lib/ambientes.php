<?php

require_once __DIR__ . '/envctl.php';
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

// Consulta o motor para atualizar ambientes registrados como em execução.
function sincronizar_status(): void
{
    $ambientes = db()->query(
        "SELECT id FROM ambientes WHERE status = 'em_execucao'"
    )->fetchAll();

    foreach ($ambientes as $ambiente) {
        $id = (int) $ambiente['id'];

        try {
            $resposta = executar_envctl('status', [$id]);

            if (($resposta['id'] ?? null) !== $id) {
                throw new RuntimeException(
                    'O motor retornou um ID diferente do solicitado.'
                );
            }

            $status = $resposta['status'] ?? null;

            if (!in_array(
                $status,
                ['em_execucao', 'terminado', 'erro'],
                true
            )) {
                throw new RuntimeException(
                    'O motor retornou um estado inesperado para um ambiente em execução.'
                );
            }

            // O status do Caio retorna pid como texto, podendo estar vazio.
            $pidRecebido = $resposta['pid'] ?? '';
            $pid = null;

            if ($status === 'em_execucao') {
                if (
                    !is_string($pidRecebido)
                    && !is_int($pidRecebido)
                ) {
                    throw new RuntimeException('PID com formato inválido.');
                }

                $pid = filter_var(
                    $pidRecebido,
                    FILTER_VALIDATE_INT,
                    ['options' => [
                        'min_range' => 1,
                        'max_range' => 4294967295,
                    ]]
                );

                if ($pid === false) {
                    throw new RuntimeException('PID inválido para ambiente em execução.');
                }
            }

            if (!array_key_exists('exit_code', $resposta)) {
                throw new RuntimeException('Resposta sem exit_code.');
            }

            $exitCode = $resposta['exit_code'];

            if (
                $exitCode !== null
                && (!is_int($exitCode) || $exitCode < 0 || $exitCode > 255)
            ) {
                throw new RuntimeException('Código de saída inválido.');
            }

            $motivo = null;

            if ($status === 'erro') {
                $motivo = $exitCode !== null && $exitCode !== 0
                    ? "Execução terminou com código $exitCode."
                    : 'O motor informou erro na execução; consulte o output.';
            }

            // Evita sobrescrever uma parada já registrada por outra requisição.
            $stmt = db()->prepare(
                "UPDATE ambientes
                 SET status = :status,
                     pid = :pid,
                     exit_code = :exit_code,
                     motivo = :motivo
                 WHERE id = :id AND status = 'em_execucao'"
            );

            $stmt->execute([
                'status' => $status,
                'pid' => $pid,
                'exit_code' => $exitCode,
                'motivo' => $motivo,
                'id' => $id,
            ]);
        } catch (Throwable $e) {
            // Uma falha de consulta não prova que o processo terminou.
            // Preserva o registro e permite consultar os demais ambientes.
            error_log(
                "Falha ao sincronizar ambiente #$id: " . $e->getMessage()
            );
        }
    }
}