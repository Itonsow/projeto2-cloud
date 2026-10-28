<?php

require_once __DIR__ . '/../lib/ambientes.php';
require_once __DIR__ . '/../lib/envctl.php';

function redirecionar(string $tipo, string $mensagem): void
{
    header(
        'Location: /index.php?' . http_build_query([$tipo => $mensagem]),
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

$idRecebido = $_POST['id'] ?? null;

$id = is_string($idRecebido)
    ? filter_var(
        $idRecebido,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

if ($id === false) {
    redirecionar('erro', 'ID do ambiente inválido.');
}

try {
    $ambiente = buscar_ambiente($id);

    if ($ambiente === null) {
        redirecionar('erro', 'Ambiente não encontrado.');
    }

    if ($ambiente['status'] !== 'em_execucao') {
        redirecionar('erro', 'O ambiente não está em execução.');
    }

    executar_envctl('stop', [$id]);

    // Só atualiza o banco se o motor confirmar sucesso.
    atualizar_ambiente($id, [
        'status' => 'terminado',
        'pid' => null,
        'exit_code' => null,
        'motivo' => 'Interrompido pelo usuário.',
    ]);

    redirecionar('ok', 'Ambiente parado com sucesso.');
} catch (Throwable $e) {
    error_log((string) $e);

    redirecionar(
        'erro',
        'Não foi possível concluir a parada. Consulte os logs do servidor.'
    );
}