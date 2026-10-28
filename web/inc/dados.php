<?php
// Ponte entre as páginas e o backend de ambientes.
$lib = __DIR__ . '/../lib/ambientes.php';
if (is_file($lib)) {
    require_once $lib;
}

function pasta_ambiente(int $id): string
{
    return rtrim(getenv('P2_ENVS_DIR') ?: '/var/lib/projeto2/envs', '/') . '/env-' . $id;
}

function pasta_cgroup(int $id): string
{
    return rtrim(getenv('P2_CGROUP_DIR') ?: '/sys/fs/cgroup/projeto2', '/') . '/env-' . $id;
}

function ambientes_sincronizados(): array
{
    if (function_exists('sincronizar_status')) {
        sincronizar_status();
    }
    return function_exists('listar_ambientes') ? listar_ambientes() : [];
}

function ambiente_sincronizado(int $id): ?array
{
    if (function_exists('sincronizar_status')) {
        sincronizar_status();
    }
    return function_exists('buscar_ambiente') ? buscar_ambiente($id) : null;
}


function memoria_em_uso(array $amb): ?int
{
    if ($amb['status'] !== 'em_execucao') {
        return null;
    }
    $valor = @file_get_contents(pasta_cgroup((int) $amb['id']) . '/memory.current');
    return $valor === false ? null : (int) trim($valor);
}
