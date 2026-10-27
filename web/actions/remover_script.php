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
    redirecionar('erro', 'ID do script inválido.');
}

try {
    if (!remover_script($id)) {
        redirecionar('erro', 'Script não encontrado ou já removido.');
    }

    redirecionar('ok', 'Script removido com sucesso.');
} catch (Throwable $e) {
    error_log((string) $e);
    redirecionar('erro', 'Não foi possível remover o script.');
}