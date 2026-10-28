<?php

require_once __DIR__ . '/validacao.php';
require_once __DIR__ . '/scripts.php';

// Prepara os dados para cadastro, sem executar o conteúdo.
function preparar_ambiente(array $entrada): array
{
    $dados = validar_campos_ambiente($entrada);
    $dados['script_id'] = null;

    if ($dados['tipo'] === 'script') {
        $scriptId = ler_inteiro(
            $entrada,
            'script_id',
            1,
            4294967295
        );

        $script = buscar_script($scriptId);

        if ($script === null) {
            throw new InvalidArgumentException(
                'O script selecionado não existe.'
            );
        }

        $conteudo = $script['conteudo'];
        $dados['script_id'] = $scriptId;
    } else {
        $conteudo = ler_texto($entrada, 'comando');
    }

    if (
        strpos($conteudo, "\0") !== false
        || preg_match('//u', $conteudo) !== 1
    ) {
        throw new InvalidArgumentException(
            'O conteúdo deve ser texto válido em UTF-8.'
        );
    }

    if (strlen($conteudo) > 1048576) {
        throw new InvalidArgumentException(
            'O conteúdo deve ter no máximo 1 MB.'
        );
    }

    // Normaliza quebras de linha do Windows para Linux.
    $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);

    if (trim($conteudo) === '') {
        throw new InvalidArgumentException(
            'Informe o conteúdo que será executado.'
        );
    }

    if ($dados['tipo'] === 'comando') {
        // Remove apenas quebras finais, preservando espaços do comando.
        $conteudo = rtrim($conteudo, "\n");

        if (strpos($conteudo, "\n") !== false) {
            throw new InvalidArgumentException(
                'Para várias linhas, escolha a opção Vários comandos.'
            );
        }
    }

    $dados['conteudo_execucao'] = $conteudo;

    return $dados;
}
