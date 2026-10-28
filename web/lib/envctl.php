<?php

// Interpreta a resposta do motor e transforma falhas em exceções.
function interpretar_resposta_envctl(
    string $saida,
    int $codigoSaida
): array {
    try {
        $resposta = json_decode(
            trim($saida),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        throw new RuntimeException(
            "O envctl retornou uma resposta inválida (código $codigoSaida).",
            0,
            $e
        );
    }

    if (
        !is_array($resposta)
        || !array_key_exists('sucesso', $resposta)
        || !is_bool($resposta['sucesso'])
    ) {
        throw new RuntimeException(
            'A resposta do envctl não contém o campo sucesso válido.'
        );
    }

    if ($codigoSaida !== 0 || $resposta['sucesso'] !== true) {
        $erro = $resposta['erro'] ?? null;

        throw new RuntimeException(
            is_string($erro) && $erro !== ''
                ? $erro
                : "Falha ao executar envctl (código $codigoSaida)."
        );
    }

    return $resposta;
}

// Executa apenas os subcomandos conhecidos do motor.
function executar_envctl(string $subcomando, array $argumentos = []): array
{
    $permitidos = [
        'init',
        'create',
        'start',
        'status',
        'stop',
        'remove',
    ];

    if (!in_array($subcomando, $permitidos, true)) {
        throw new InvalidArgumentException(
            'Subcomando envctl não permitido.'
        );
    }

    $partes = [
        '/usr/bin/sudo',
        '-n',
        '/usr/local/bin/envctl',
        $subcomando,
    ];

    foreach ($argumentos as $argumento) {
        if (!is_string($argumento) && !is_int($argumento)) {
            throw new InvalidArgumentException(
                'Os argumentos do envctl devem ser textos ou inteiros.'
            );
        }

        $argumento = (string) $argumento;

        if (strpos($argumento, "\0") !== false) {
            throw new InvalidArgumentException(
                'Argumento envctl contém caractere inválido.'
            );
        }

        $partes[] = $argumento;
    }

    $comando = implode(' ', array_map('escapeshellarg', $partes));

    // Arquivo temporário para separar mensagens de erro do JSON.
    $stderr = tmpfile();

    if ($stderr === false) {
        throw new RuntimeException(
            'Não foi possível preparar a execução do envctl.'
        );
    }

    try {
        $descritores = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => $stderr,
        ];

        $processo = proc_open($comando, $descritores, $pipes);

        if (!is_resource($processo)) {
            throw new RuntimeException('Não foi possível iniciar o envctl.');
        }

        $saida = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $codigoSaida = proc_close($processo);

        rewind($stderr);
        $erroTecnico = stream_get_contents($stderr);

        if ($erroTecnico !== false && trim($erroTecnico) !== '') {
            error_log('envctl stderr: ' . trim($erroTecnico));
        }

        if ($saida === false) {
            throw new RuntimeException(
                'Não foi possível ler a resposta do envctl.'
            );
        }

        return interpretar_resposta_envctl($saida, $codigoSaida);
    } finally {
        fclose($stderr);
    }
}