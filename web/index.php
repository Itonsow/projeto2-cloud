<?php
$titulo = 'Ambientes';
$pagina = 'ambientes';
require __DIR__ . '/inc/header.php';
?>
<!-- X4: tabela de ambientes com status e auto-refresh via api/status.php -->
<div class="estado-vazio">
    <p class="mb-3">Nenhum ambiente criado ainda.</p>
    <a class="btn btn-primary" href="/novo.php">Criar ambiente</a>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
