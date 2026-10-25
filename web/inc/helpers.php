<?php
// Funções utilitárias usadas por todas as páginas.

// Escapa texto para exibir com segurança no HTML.
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Rótulo legível e classe CSS de cada status do ambiente (ENUM do banco).
const STATUS_AMBIENTE = [
    'criado'      => ['rotulo' => 'Criado',      'classe' => 'status-criado'],
    'em_execucao' => ['rotulo' => 'Em execução', 'classe' => 'status-em_execucao'],
    'terminado'   => ['rotulo' => 'Terminado',   'classe' => 'status-terminado'],
    'erro'        => ['rotulo' => 'Erro',        'classe' => 'status-erro'],
];

// Badge colorido do status, usado no painel e na página do ambiente.
function badge_status(string $status): string
{
    $info = STATUS_AMBIENTE[$status] ?? ['rotulo' => $status, 'classe' => 'status-criado'];
    return '<span class="badge ' . e($info['classe']) . '">' . e($info['rotulo']) . '</span>';
}
