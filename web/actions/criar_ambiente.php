<?php

require_once __DIR__ . '/../lib/ambientes.php';
require_once __DIR__ . '/../lib/preparacao.php';
require_once __DIR__ . '/../lib/envctl.php';

function redirecionar(string $tipo, string $mensagem): void
{
    header(
        'Location: /novo.php?' . http_build_query([$tipo => $mensagem]),
        true,
        303
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método não permitido.');
}

$id = null;
$pasta = null;
$pastaCriada = false;
$motorTentado = false;

try {
    $dados = preparar_ambiente($_POST);

    $base = '/var/lib/projeto2/envs';

    if (!is_dir($base) || !is_writable($base)) {
        throw new RuntimeException(
            'A pasta de ambientes não permite gravação pelo PHP.'
        );
    }

    if (!is_executable('/usr/local/bin/envctl')) {
        throw new RuntimeException('O envctl não está instalado ou executável.');
    }

    $id = criar_ambiente($dados);
    $pasta = $base . '/env-' . $id;

    // Não reutiliza uma pasta antiga que tenha o mesmo ID.
    if (file_exists($pasta) || is_link($pasta)) {
        throw new RuntimeException('Já existe uma pasta para esse ambiente.');
    }

    // O PHP cria a pasta antes do motor para poder gravar script.sh.
    if (!mkdir($pasta, 0750)) {
        throw new RuntimeException('Não foi possível criar a pasta do ambiente.');
    }

    $pastaCriada = true;
    $arquivo = $pasta . '/script.sh';
    $conteudo = $dados['conteudo_execucao'];

    $gravados = file_put_contents($arquivo, $conteudo, LOCK_EX);

    if ($gravados === false || $gravados !== strlen($conteudo)) {
        throw new RuntimeException('Não foi possível gravar todo o script.');
    }

    if (!chmod($arquivo, 0640)) {
        throw new RuntimeException('Não foi possível definir a permissão do script.');
    }

    $argumentos = [
        $id,
        '--cpu', $dados['cpu_percentual'],
        '--mem', $dados['memoria_mb'],
    ];

    if ($dados['io_leitura_mbps'] !== null) {
        $argumentos[] = '--io-read';
        $argumentos[] = $dados['io_leitura_mbps'];
    }

    if ($dados['io_escrita_mbps'] !== null) {
        $argumentos[] = '--io-write';
        $argumentos[] = $dados['io_escrita_mbps'];
    }

    if ($dados['isolar_rede'] === 1) {
        $argumentos[] = '--net-isolate';
    }

    // Marca antes da chamada: mesmo uma falha pode deixar recursos parciais.
    $motorTentado = true;
    executar_envctl('create', $argumentos);

    $resposta = executar_envctl('start', [$id]);
    $pid = $resposta['pid'] ?? null;

    if (!is_int($pid) || $pid < 1 || $pid > 4294967295) {
        throw new RuntimeException('O motor não retornou um PID válido.');
    }

    atualizar_ambiente($id, [
        'status' => 'em_execucao',
        'pid' => $pid,
        'exit_code' => null,
        'motivo' => null,
    ]);
} catch (Throwable $e) {
    error_log((string) $e);
    $limpezaConcluida = true;

    if ($id !== null) {
        try {
            if ($motorTentado) {
                executar_envctl('remove', [$id]);
            } elseif ($pastaCriada) {
                $arquivo = $pasta . '/script.sh';

                if (file_exists($arquivo) && !unlink($arquivo)) {
                    throw new RuntimeException('Falha ao apagar script.sh.');
                }

                if (!rmdir($pasta)) {
                    throw new RuntimeException('Falha ao apagar a pasta.');
                }
            }

            // Apaga o registro depois da limpeza dos recursos.
            remover_ambiente($id);
        } catch (Throwable $erroLimpeza) {
            $limpezaConcluida = false;
            error_log((string) $erroLimpeza);

            // Mantém um registro para identificar os recursos pendentes.
            try {
                atualizar_ambiente($id, [
                    'status' => 'erro',
                    'motivo' => 'Falha na criação; limpeza pendente. '
                        . 'Pode haver recursos ou processos ativos.',
                ]);
            } catch (Throwable $erroBanco) {
                error_log((string) $erroBanco);
            }
        }
    }

    if (!$limpezaConcluida) {
        redirecionar(
            'erro',
            "Falha na criação do ambiente #$id e na limpeza. "
            . 'Pode haver recursos ativos. Consulte os logs do servidor.'
        );
    }

    redirecionar(
        'erro',
        $e instanceof InvalidArgumentException
            ? $e->getMessage()
            : 'Não foi possível criar o ambiente. Consulte os logs do servidor.'
    );
}

header(
    'Location: /ambiente.php?' . http_build_query(['id' => $id]),
    true,
    303
);
exit;