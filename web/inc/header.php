<?php
// Cabeçalho 
require_once __DIR__ . '/helpers.php';

$titulo = $titulo ?? 'Projeto 2';
$pagina = $pagina ?? '';

$menu = [
    'ambientes' => ['href' => '/index.php',   'rotulo' => 'Ambientes'],
    'novo'      => ['href' => '/novo.php',    'rotulo' => 'Novo ambiente'],
    'scripts'   => ['href' => '/scripts.php', 'rotulo' => 'Scripts'],
];
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Projeto 2</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="d-flex flex-column min-vh-100"
      data-status-rotulos="<?= e(json_encode(array_map(fn ($s) => $s['rotulo'], STATUS_AMBIENTE), JSON_UNESCAPED_UNICODE)) ?>">
<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/index.php">Projeto 2 · Ambientes isolados</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                aria-controls="menu" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($menu as $chave => $item): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $chave === $pagina ? ' active' : '' ?>"
                           <?= $chave === $pagina ? 'aria-current="page"' : '' ?>
                           href="<?= e($item['href']) ?>"><?= e($item['rotulo']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container flex-grow-1 py-4">
    <h1 class="h3 mb-4"><?= e($titulo) ?></h1>
    <?= alerta_flash() ?>
