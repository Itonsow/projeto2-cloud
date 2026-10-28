<?php
// Detalhes de um ambiente: limites, estado e output, atualizados pelo app.js.
require_once __DIR__ . '/inc/dados.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$amb = null;
$erro_dados = null;
if ($id) {
    try {
        $amb = ambiente_sincronizado($id);
    } catch (Throwable $e) {
        error_log((string) $e);
        $erro_dados = 'Não foi possível consultar o ambiente. Verifique se o MySQL está ativo.';
    }
}

$titulo = $amb ? $amb['nome'] : ($id ? "Ambiente #$id" : 'Ambiente');
$pagina = 'ambientes';
require __DIR__ . '/inc/header.php';

$io = fn ($mbps) => $mbps === null ? 'sem limite' : $mbps . ' MB/s';
?>
<?php if (!$id): ?>
    <div class="alert alert-warning">Ambiente inválido. <a href="/index.php">Voltar para a lista</a>.</div>
<?php elseif ($erro_dados): ?>
    <div class="alert alert-danger"><?= e($erro_dados) ?></div>
<?php elseif (!$amb): ?>
    <div class="alert alert-warning">Ambiente #<?= $id ?> não encontrado. <a href="/index.php">Voltar para a lista</a>.</div>
<?php else: ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card" data-ambiente="<?= $id ?>" data-mem-limite="<?= (int) $amb['memoria_mb'] ?>">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            <span data-campo="status"><?= badge_status($amb['status']) ?></span>
                            <div class="text-muted" data-campo="motivo"><?= e(detalhe_status($amb)) ?></div>
                        </dd>
                        <dt class="col-5">Código de saída</dt>
                        <dd class="col-7" data-campo="exit_code"><?= $amb['exit_code'] === null ? '—' : (int) $amb['exit_code'] ?></dd>
                        <dt class="col-5">Memória em uso</dt>
                        <dd class="col-7" data-campo="memoria"><?= e(memoria_texto(memoria_em_uso($amb), (int) $amb['memoria_mb'])) ?></dd>
                        <dt class="col-5">CPU</dt>
                        <dd class="col-7"><?= (int) $amb['cpu_percentual'] ?>% de 1 CPU</dd>
                        <dt class="col-5">Limite de memória</dt>
                        <dd class="col-7"><?= (int) $amb['memoria_mb'] ?> MB</dd>
                        <dt class="col-5">IO leitura</dt>
                        <dd class="col-7"><?= e($io($amb['io_leitura_mbps'])) ?></dd>
                        <dt class="col-5">IO escrita</dt>
                        <dd class="col-7"><?= e($io($amb['io_escrita_mbps'])) ?></dd>
                        <dt class="col-5">Rede</dt>
                        <dd class="col-7"><?= $amb['isolar_rede'] ? 'Isolada' : 'Compartilhada com a VM' ?></dd>
                        <dt class="col-5">Execução</dt>
                        <dd class="col-7"><?= e(TIPO_EXECUCAO[$amb['tipo']] ?? $amb['tipo']) ?></dd>
                        <dt class="col-5">PID</dt>
                        <dd class="col-7"><?= $amb['pid'] === null ? '—' : (int) $amb['pid'] ?></dd>
                        <dt class="col-5">Criado em</dt>
                        <dd class="col-7 mb-0"><?= e(data_br($amb['criado_em'])) ?></dd>
                    </dl>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <form method="post" action="/actions/parar.php" data-so-em-execucao
                          data-confirmar="Parar o ambiente &quot;<?= e($amb['nome']) ?>&quot;?"
                          <?= $amb['status'] === 'em_execucao' ? '' : 'hidden' ?>>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning">Parar</button>
                    </form>
                    <form method="post" action="/actions/remover.php"
                          data-confirmar="Remover o ambiente &quot;<?= e($amb['nome']) ?>&quot;? O output também será apagado.">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h2 class="h6 card-title">O que executa</h2>
                    <pre class="output small mb-0"><?= e($amb['conteudo_execucao']) ?></pre>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h2 class="h5 mb-0">Output</h2>
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="acompanhar" checked>
                        <label class="form-check-label small" for="acompanhar">Acompanhar o final</label>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="/api/output.php?id=<?= $id ?>&amp;download=1">Baixar log</a>
                </div>
            </div>
            <p class="small text-muted mb-2" id="output-truncado" hidden>Mostrando só os últimos 64 KB. Baixe o log para ver tudo.</p>
            <pre class="output" id="output" data-url="/api/output.php?id=<?= $id ?>">Carregando…</pre>
            <p class="small text-muted">Atualizado a cada 2 segundos.</p>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
