<?php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$titulo = $id ? "Ambiente #$id" : 'Ambiente';
$pagina = 'ambientes';
require __DIR__ . '/inc/header.php';
?>
<?php if (!$id): ?>
    <div class="alert alert-warning">Ambiente inválido. <a href="/index.php">Voltar para a lista</a>.</div>
<?php else: ?>
    <!-- X5: limites, estado, código de saída, consumo e output (api/output.php) -->
    <div class="estado-vazio">
        <p class="mb-0">Detalhes e output do ambiente em construção.</p>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
