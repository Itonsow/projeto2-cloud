<?php
require_once __DIR__ . '/inc/dados.php';

$erro_dados = null;
try {
    $ambientes = ambientes_sincronizados();
} catch (Throwable $e) {
    error_log((string) $e);
    $ambientes = [];
    $erro_dados = 'Não foi possível consultar os ambientes. Verifique se o MySQL está ativo.';
}

$titulo = 'Ambientes';
$pagina = 'ambientes';
require __DIR__ . '/inc/header.php';
?>
<div id="painel" data-painel>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted small mb-0">O status é atualizado automaticamente a cada 3 segundos.</p>
        <a class="btn btn-primary" href="/novo.php">Novo ambiente</a>
    </div>

    <?php if ($erro_dados): ?>
        <div class="alert alert-danger"><?= e($erro_dados) ?></div>
    <?php elseif (!$ambientes): ?>
        <div class="estado-vazio">
            <p class="mb-3">Nenhum ambiente criado ainda.</p>
            <a class="btn btn-primary" href="/novo.php">Criar ambiente</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Ambiente</th>
                        <th>Limites</th>
                        <th>Status</th>
                        <th>Memória em uso</th>
                        <th>Criado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ambientes as $amb): ?>
                        <?php $id = (int) $amb['id']; ?>
                        <tr data-ambiente="<?= $id ?>" data-mem-limite="<?= (int) $amb['memoria_mb'] ?>">
                            <td>
                                <a class="fw-semibold" href="/ambiente.php?id=<?= $id ?>"><?= e($amb['nome']) ?></a>
                                <div class="small text-muted">#<?= $id ?> · <?= e(TIPO_EXECUCAO[$amb['tipo']] ?? $amb['tipo']) ?></div>
                            </td>
                            <td class="small text-nowrap"><?= e(limites_texto($amb)) ?></td>
                            <td>
                                <span data-campo="status"><?= badge_status($amb['status']) ?></span>
                                <div class="small text-muted" data-campo="motivo"><?= e(detalhe_status($amb)) ?></div>
                            </td>
                            <td class="small text-nowrap" data-campo="memoria">
                                <?= e(memoria_texto(memoria_em_uso($amb), (int) $amb['memoria_mb'])) ?>
                            </td>
                            <td class="small text-nowrap"><?= e(data_br($amb['criado_em'])) ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="/ambiente.php?id=<?= $id ?>">Ver</a>
                                <form method="post" action="/actions/parar.php" class="d-inline" data-so-em-execucao
                                      data-confirmar="Parar o ambiente &quot;<?= e($amb['nome']) ?>&quot;?"
                                      <?= $amb['status'] === 'em_execucao' ? '' : 'hidden' ?>>
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Parar</button>
                                </form>
                                <form method="post" action="/actions/remover.php" class="d-inline"
                                      data-confirmar="Remover o ambiente &quot;<?= e($amb['nome']) ?>&quot;? O output também será apagado.">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
