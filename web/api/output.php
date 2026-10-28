<?php

require_once __DIR__ . '/../inc/dados.php';

const LIMITE_OUTPUT = 65536;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('ID inválido.');
}

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$arquivo = pasta_ambiente($id) . '/output.log';
if (!is_file($arquivo)) {
    http_response_code(404);
    exit;
}

if (isset($_GET['download'])) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="ambiente-' . $id . '-output.log"');
    header('Content-Length: ' . filesize($arquivo));
    readfile($arquivo);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

$tamanho = filesize($arquivo);
$inicio = max(0, $tamanho - LIMITE_OUTPUT);
$texto = file_get_contents($arquivo, false, null, $inicio);

if ($inicio > 0) {
    $quebra = strpos($texto, "\n");
    $texto = $quebra === false ? $texto : substr($texto, $quebra + 1);
    header('X-Output-Truncado: 1');
}

echo $texto;
