<?php
// Biblioteca de scripts: envio por formulário e lista dos scripts salvos para reuso.
// Os dados vêm de lib/scripts.php; enquanto ela não existir, a lista fica vazia.
$lib = __DIR__ . '/lib/scripts.php';
if (is_file($lib)) {
    require_once $lib;
}
$scripts = function_exists('listar_scripts') ? listar_scripts() : [];

$titulo = 'Scripts';
$pagina = 'scripts';
require __DIR__ . '/inc/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 card-title mb-3">Enviar script</h2>
                <form method="post" action="/actions/salvar_script.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição <span class="text-muted">(opcional)</span></label>
                        <input type="text" class="form-control" id="descricao" name="descricao" maxlength="255">
                    </div>

                    <div class="btn-group w-100 mb-3" role="group" aria-label="Origem do script">
                        <input type="radio" class="btn-check" name="origem" id="origem-arquivo" value="arquivo" checked>
                        <label class="btn btn-outline-secondary" for="origem-arquivo">Enviar arquivo</label>
                        <input type="radio" class="btn-check" name="origem" id="origem-texto" value="texto">
                        <label class="btn btn-outline-secondary" for="origem-texto">Colar texto</label>
                    </div>

                    <div class="mb-3" data-opcao="origem:arquivo">
                        <label for="arquivo" class="form-label">Arquivo</label>
                        <input type="file" class="form-control" id="arquivo" name="arquivo"
                               accept=".sh,text/x-sh,text/plain" data-max-bytes="1048576" required>
                        <div class="form-text">Arquivo .sh de até 1 MB.</div>
                    </div>
                    <div class="mb-3" data-opcao="origem:texto" hidden>
                        <label for="conteudo" class="form-label">Conteúdo</label>
                        <textarea class="form-control font-monospace" id="conteudo" name="conteudo" rows="10"
                                  placeholder="#!/bin/bash&#10;echo &quot;olá&quot;" required disabled></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar script</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <h2 class="h5 mb-3">Scripts salvos</h2>
        <?php if (!$scripts): ?>
            <div class="estado-vazio">
                <p class="mb-0">Nenhum script salvo ainda.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Criado em</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scripts as $s): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($s['nome']) ?></td>
                                <td class="text-muted"><?= e($s['descricao'] ?: '—') ?></td>
                                <td class="text-nowrap"><?= e(data_br($s['criado_em'])) ?></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#script-<?= (int) $s['id'] ?>">Ver</button>
                                    <form method="post" action="/actions/remover_script.php" class="d-inline"
                                          data-confirmar="Remover o script &quot;<?= e($s['nome']) ?>&quot;?">
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($scripts as $s): ?>
                <div class="modal fade" id="script-<?= (int) $s['id'] ?>" tabindex="-1"
                     aria-labelledby="script-<?= (int) $s['id'] ?>-titulo" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title h5" id="script-<?= (int) $s['id'] ?>-titulo"><?= e($s['nome']) ?></h3>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <pre class="output mb-0"><?= e($s['conteudo']) ?></pre>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
