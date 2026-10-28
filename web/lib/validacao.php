<?php

function ler_texto(array $dados, string $campo): string
{
    $valor = $dados[$campo] ?? '';

    if (!is_string($valor)) {
        throw new InvalidArgumentException(
            "O campo $campo deve ser um texto."
        );
    }

    return $valor;
}

function ler_inteiro(
    array $dados,
    string $campo,
    int $minimo,
    int $maximo,
    bool $opcional = false
): ?int {
    $texto = trim(ler_texto($dados, $campo));

    if ($opcional && $texto === '') {
        return null;
    }

    $valor = filter_var(
        $texto,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => $minimo,
                'max_range' => $maximo,
            ],
        ]
    );

    if ($valor === false) {
        throw new InvalidArgumentException(
            "O campo $campo deve ser um inteiro entre $minimo e $maximo."
        );
    }

    return $valor;
}

function validar_campos_ambiente(array $dados): array
{
    $nome = trim(ler_texto($dados, 'nome'));
    $tamanho = preg_match_all('/./us', $nome);

    if (
        $tamanho === false
        || $tamanho < 1
        || $tamanho > 100
        || strpos($nome, "\0") !== false
    ) {
        throw new InvalidArgumentException(
            'Informe um nome válido de até 100 caracteres.'
        );
    }

    $tipo = ler_texto($dados, 'tipo');

    if (!in_array($tipo, ['comando', 'comandos', 'script'], true)) {
        throw new InvalidArgumentException(
            'Escolha um tipo de execução válido.'
        );
    }

    $rede = ler_texto($dados, 'isolar_rede');

    if (!in_array($rede, ['', '0', '1'], true)) {
        throw new InvalidArgumentException(
            'O campo isolar_rede deve ser 0 ou 1.'
        );
    }

    return [
        'nome' => $nome,
        'cpu_percentual' => ler_inteiro(
            $dados, 'cpu_pct', 5, 100
        ),
        'memoria_mb' => ler_inteiro(
            $dados, 'mem_mb', 32, 2048
        ),
        'io_leitura_mbps' => ler_inteiro(
            $dados, 'io_rbps_mb', 1, 4294967295, true
        ),
        'io_escrita_mbps' => ler_inteiro(
            $dados, 'io_wbps_mb', 1, 4294967295, true
        ),
        'isolar_rede' => $rede === '1' ? 1 : 0,
        'tipo' => $tipo,
    ];
}