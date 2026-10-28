<?php
// Formulário de criação de ambiente. Envia para actions/criar_ambiente.php
// com os campos do contrato. Os scripts do select vêm de lib/scripts.php.
require_once __DIR__ . '/lib/scripts.php';

$erro_dados = null;
try {
    $scripts = listar_scripts();
} catch (Throwable $e) {
    error_log((string) $e);
    $scripts = [];
    $erro_dados = 'Não foi possível carregar os scripts salvos. Verifique se o MySQL está ativo.';
}

$titulo = 'Novo ambiente';
$pagina = 'novo';
require __DIR__ . '/inc/header.php';
?>
<?php if ($erro_dados): ?>
    <div class="alert alert-danger"><?= e($erro_dados) ?></div>
<?php endif; ?>
<form method="post" action="/actions/criar_ambiente.php" class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 card-title mb-3">Ambiente e limites</h2>
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label for="cpu_pct" class="form-label">CPU</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="cpu_pct" name="cpu_pct"
                               min="5" max="100" step="1" value="50" required>
                        <span class="input-group-text">% de 1 CPU</span>
                    </div>
                    <div class="form-text">De 5 a 100.</div>
                </div>
                <div class="mb-3">
                    <label for="mem_mb" class="form-label">Memória</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="mem_mb" name="mem_mb"
                               min="32" max="2048" step="1" value="256" required>
                        <span class="input-group-text">MB</span>
                    </div>
                    <div class="form-text">De 32 a 2048.</div>
                </div>
                <div class="row g-3 mb-1">
                    <div class="col-sm-6">
                        <label for="io_rbps_mb" class="form-label">IO leitura</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="io_rbps_mb" name="io_rbps_mb"
                                   min="1" step="1" placeholder="sem limite">
                            <span class="input-group-text">MB/s</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label for="io_wbps_mb" class="form-label">IO escrita</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="io_wbps_mb" name="io_wbps_mb"
                                   min="1" step="1" placeholder="sem limite">
                            <span class="input-group-text">MB/s</span>
                        </div>
                    </div>
                </div>
                <div class="form-text mb-3">Deixe em branco para não limitar.</div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="isolar_rede" name="isolar_rede" value="1">
                    <label class="form-check-label" for="isolar_rede">Isolar rede</label>
                    <div class="form-text">O ambiente fica sem acesso à rede (namespace de rede próprio).</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 card-title mb-3">O que executar</h2>
                <div class="btn-group w-100 mb-3" role="group" aria-label="Tipo de execução">
                    <input type="radio" class="btn-check" name="tipo" id="tipo-comando" value="comando" checked>
                    <label class="btn btn-outline-secondary" for="tipo-comando">Comando único</label>
                    <input type="radio" class="btn-check" name="tipo" id="tipo-comandos" value="comandos">
                    <label class="btn btn-outline-secondary" for="tipo-comandos">Vários comandos</label>
                    <input type="radio" class="btn-check" name="tipo" id="tipo-script" value="script">
                    <label class="btn btn-outline-secondary" for="tipo-script">Script salvo</label>
                </div>

                <!-- Comando único e vários comandos usam o mesmo campo "comando"; só o bloco visível é enviado. -->
                <div class="mb-3" data-opcao="tipo:comando">
                    <label for="comando-unico" class="form-label">Comando</label>
                    <input type="text" class="form-control font-monospace" id="comando-unico" name="comando"
                           placeholder="python3 -m http.server 8000" required>
                </div>
                <div class="mb-3" data-opcao="tipo:comandos" hidden>
                    <label for="comando-varios" class="form-label">Comandos</label>
                    <textarea class="form-control font-monospace" id="comando-varios" name="comando" rows="8"
                              placeholder="hostname&#10;ps aux&#10;sleep 60" required disabled></textarea>
                    <div class="form-text">Um comando por linha, executados em sequência.</div>
                </div>
                <div class="mb-3" data-opcao="tipo:script" hidden>
                    <label for="script_id" class="form-label">Script</label>
                    <select class="form-select" id="script_id" name="script_id" required disabled>
                        <?php if (!$scripts): ?>
                            <option value="">Nenhum script salvo</option>
                        <?php else: ?>
                            <option value="">Escolha um script…</option>
                            <?php foreach ($scripts as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= e($s['nome']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">Gerencie a biblioteca em <a href="/scripts.php">Scripts</a>.</div>
                </div>
            </div>
            <div class="card-footer bg-transparent text-end">
                <a href="/index.php" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Criar e executar</button>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/inc/footer.php'; ?>
