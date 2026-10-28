<?php
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

const STATUS_AMBIENTE = [
    'criado'      => ['rotulo' => 'Criado',      'classe' => 'status-criado'],
    'em_execucao' => ['rotulo' => 'Em execução', 'classe' => 'status-em_execucao'],
    'terminado'   => ['rotulo' => 'Terminado',   'classe' => 'status-terminado'],
    'erro'        => ['rotulo' => 'Erro',        'classe' => 'status-erro'],
];

function badge_status(string $status): string
{
    $info = STATUS_AMBIENTE[$status] ?? ['rotulo' => $status, 'classe' => 'status-criado'];
    return '<span class="badge ' . e($info['classe']) . '">' . e($info['rotulo']) . '</span>';
}

function detalhe_status(array $amb): string
{
    if (!empty($amb['motivo'])) {
        return $amb['motivo'];
    }
    if ($amb['exit_code'] !== null && $amb['status'] !== 'em_execucao') {
        return 'código de saída ' . $amb['exit_code'];
    }
    return '';
}

const TIPO_EXECUCAO = [
    'comando'  => 'Comando único',
    'comandos' => 'Vários comandos',
    'script'   => 'Script salvo',
];

function formatar_mb(int $bytes): string
{
    return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
}

function memoria_texto(?int $bytes, int $limite_mb): string
{
    return $bytes === null ? '—' : formatar_mb($bytes) . ' de ' . $limite_mb . ' MB';
}

function limites_texto(array $amb): string
{
    $io = fn ($mbps) => $mbps === null ? 'livre' : $mbps . ' MB/s';
    return 'CPU ' . $amb['cpu_percentual'] . '% · ' . $amb['memoria_mb'] . ' MB · '
        . 'IO ' . $io($amb['io_leitura_mbps']) . ' / ' . $io($amb['io_escrita_mbps']);
}

function data_br(?string $data): string
{
    return $data ? date('d/m/Y H:i', strtotime($data)) : '';
}

function alerta_flash(): string
{
    $html = '';
    foreach (['ok' => 'success', 'erro' => 'danger'] as $param => $cor) {
        $msg = $_GET[$param] ?? '';
        if (is_string($msg) && $msg !== '') {
            $html .= '<div class="alert alert-' . $cor . ' alert-dismissible fade show" role="alert">'
                . e($msg)
                . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>'
                . '</div>';
        }
    }
    return $html;
}
