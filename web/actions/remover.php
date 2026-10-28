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

$motorRemoveu = false;

try {
    $ambiente = buscar_ambiente($id);

    if ($ambiente === null) {
        redirecionar('erro', 'Ambiente não encontrado ou já removido.');
    }

    // Pelo plano do C4, remove também para o ambiente,
    // espera os processos terminarem e limpa seus arquivos.
    executar_envctl('remove', [$id]);
    $motorRemoveu = true;

    remover_ambiente($id);

    redirecionar('ok', 'Ambiente removido com sucesso.');
} catch (Throwable $e) {
    error_log((string) $e);

    $mensagem = $motorRemoveu
        ? 'O motor removeu o ambiente, mas houve uma falha ao excluir '
            . 'o registro no banco. Consulte os logs do servidor.'
        : 'Não foi possível remover o ambiente pelo motor. '
            . 'O registro foi mantido no banco. Consulte os logs do servidor.';

    redirecionar('erro', $mensagem);
}
