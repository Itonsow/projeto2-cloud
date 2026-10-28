<?php
require_once __DIR__ . '/../inc/dados.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $lista = array_map(fn (array $amb) => [
        'id'        => (int) $amb['id'],
        'status'    => $amb['status'],
        'exit_code' => $amb['exit_code'] === null ? null : (int) $amb['exit_code'],
        'motivo'    => $amb['motivo'],
        'mem_bytes' => memoria_em_uso($amb),
    ], ambientes_sincronizados());

    echo json_encode($lista, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo json_encode(['erro' => 'Não foi possível consultar os ambientes.'], JSON_UNESCAPED_UNICODE);
}
