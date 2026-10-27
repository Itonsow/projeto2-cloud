<?php

require_once __DIR__ . '/../lib/scripts.php';

function redirecionar(string $tipo, string $mensagem): void
{
    header(
        'Location: /scripts.php?' . http_build_query([$tipo => $mensagem]),
        true,
        303
    );
    exit;
}

function campo_texto(string $nome): string
{
    $valor = $_POST[$nome] ?? '';

    if (!is_string($valor)) {
        throw new InvalidArgumentException('Campo inválido: ' . $nome);
    }

    return $valor;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método não permitido.');
}

try {
    $nome = trim(campo_texto('nome'));
    $descricao = trim(campo_texto('descricao'));
    $origem = campo_texto('origem');

    // Confere UTF-8 e conta caracteres sem depender do mbstring.
    $tamanhoNome = preg_match_all('/./us', $nome);
    $tamanhoDescricao = preg_match_all('/./us', $descricao);

    if ($tamanhoNome === false || $tamanhoNome < 1 || $tamanhoNome > 100) {
        throw new InvalidArgumentException(
            'Informe um nome válido de até 100 caracteres.'
        );
    }

    if ($tamanhoDescricao === false || $tamanhoDescricao > 255) {
        throw new InvalidArgumentException(
            'A descrição deve ter até 255 caracteres válidos.'
        );
    }

    $limite = 1048576; // 1 MiB.

    if ($origem === 'texto') {
        $conteudo = campo_texto('conteudo');
    } elseif ($origem === 'arquivo') {
        $arquivo = $_FILES['arquivo'] ?? null;

        if (!is_array($arquivo)) {
            throw new InvalidArgumentException('Selecione um arquivo .sh.');
        }

        $erro = $arquivo['error'] ?? null;

        if (
            $erro === UPLOAD_ERR_INI_SIZE ||
            $erro === UPLOAD_ERR_FORM_SIZE
        ) {
            throw new InvalidArgumentException(
                'O arquivo excedeu o limite permitido pelo servidor.'
            );
        }

        if ($erro !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(
                'O envio do arquivo falhou. Selecione o arquivo novamente.'
            );
        }

        $nomeArquivo = $arquivo['name'] ?? null;
        $temporario = $arquivo['tmp_name'] ?? null;

        if (
            !is_string($nomeArquivo) ||
            !is_string($temporario) ||
            !is_uploaded_file($temporario)
        ) {
            throw new InvalidArgumentException('Upload inválido.');
        }

        if (strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION)) !== 'sh') {
            throw new InvalidArgumentException('Envie um arquivo com extensão .sh.');
        }

        // Lê no máximo o limite mais um byte para detectar excesso.
        $conteudo = file_get_contents(
            $temporario,
            false,
            null,
            0,
            $limite + 1
        );

        if ($conteudo === false) {
            throw new RuntimeException('Falha ao ler o arquivo enviado.');
        }
    } else {
        throw new InvalidArgumentException(
            'Escolha enviar arquivo ou colar texto.'
        );
    }

    if (strlen($conteudo) > $limite) {
        throw new InvalidArgumentException(
            'O conteúdo deve ter no máximo 1 MB.'
        );
    }

    if (trim($conteudo) === '') {
        throw new InvalidArgumentException('O script não pode estar vazio.');
    }

    if (
        strpos($conteudo, "\0") !== false ||
        preg_match('//u', $conteudo) !== 1
    ) {
        throw new InvalidArgumentException(
            'O script deve ser um arquivo de texto em UTF-8.'
        );
    }

    // Converte quebras de linha do Windows para o formato Linux.
    $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);

    salvar_script($nome, $descricao, $conteudo);

    redirecionar('ok', 'Script salvo com sucesso.');
} catch (InvalidArgumentException $e) {
    redirecionar('erro', $e->getMessage());
} catch (Throwable $e) {
    error_log((string) $e);
    redirecionar('erro', 'Não foi possível salvar o script.');
}