// Pede confirmação antes de enviar formulários marcados com data-confirmar.
document.addEventListener('submit', function (ev) {
    const msg = ev.target.dataset.confirmar;
    if (msg && !confirm(msg)) {
        ev.preventDefault();
    }
});

// Blocos que dependem de um radio: data-opcao="grupo:valor" só aparece quando o
// radio name="grupo" está com esse valor (origem do script, tipo de execução).
// O bloco escondido fica desabilitado para não ser validado nem enviado no POST.
function mostrarOpcao(grupo, valor) {
    document.querySelectorAll('[data-opcao^="' + grupo + ':"]').forEach(function (bloco) {
        const ativo = bloco.dataset.opcao === grupo + ':' + valor;
        bloco.hidden = !ativo;
        bloco.querySelectorAll('input, textarea, select').forEach(function (campo) {
            campo.disabled = !ativo;
        });
    });
}

document.querySelectorAll('input[type="radio"]').forEach(function (radio) {
    if (!document.querySelector('[data-opcao^="' + radio.name + ':"]')) {
        return;
    }
    radio.addEventListener('change', function () {
        mostrarOpcao(radio.name, radio.value);
    });
    // O navegador pode restaurar a opção marcada ao voltar para a página.
    if (radio.checked) {
        mostrarOpcao(radio.name, radio.value);
    }
});

// Status dos ambientes: a cada 3 s consulta api/status.php e atualiza cada
// elemento data-ambiente="id" (linha do painel ou card da página do ambiente).
const ROTULOS_STATUS = JSON.parse(document.body.dataset.statusRotulos || '{}');

// Mesma regra de detalhe_status() no helpers.php.
function detalheStatus(amb) {
    if (amb.motivo) {
        return amb.motivo;
    }
    if (amb.exit_code !== null && amb.status !== 'em_execucao') {
        return 'código de saída ' + amb.exit_code;
    }
    return '';
}

function formatarMb(bytes) {
    return (bytes / 1048576).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' MB';
}

function aplicarStatus(elemento, amb) {
    const campo = function (nome) {
        return elemento.querySelector('[data-campo="' + nome + '"]');
    };

    const status = campo('status');
    if (status) {
        const badge = document.createElement('span');
        badge.className = 'badge status-' + amb.status;
        badge.textContent = ROTULOS_STATUS[amb.status] || amb.status;
        status.replaceChildren(badge);
    }
    if (campo('motivo')) {
        campo('motivo').textContent = detalheStatus(amb);
    }
    if (campo('exit_code')) {
        campo('exit_code').textContent = amb.exit_code === null ? '—' : amb.exit_code;
    }
    if (campo('memoria')) {
        campo('memoria').textContent = amb.mem_bytes === null
            ? '—'
            : formatarMb(amb.mem_bytes) + ' de ' + elemento.dataset.memLimite + ' MB';
    }
    elemento.querySelectorAll('[data-so-em-execucao]').forEach(function (item) {
        item.hidden = amb.status !== 'em_execucao';
    });
}

async function atualizarStatus() {
    let lista;
    try {
        const resposta = await fetch('/api/status.php', { cache: 'no-store' });
        if (!resposta.ok) {
            return;
        }
        lista = await resposta.json();
    } catch (erro) {
        return; 
    }

    const porId = new Map(lista.map(function (amb) {
        return [String(amb.id), amb];
    }));

    const painel = document.querySelector('[data-painel]');
    if (painel) {
        const naTela = Array.from(painel.querySelectorAll('[data-ambiente]')).map(function (linha) {
            return linha.dataset.ambiente;
        });
        if (naTela.length !== porId.size || naTela.some(function (id) { return !porId.has(id); })) {
            location.reload();
            return;
        }
    }

    document.querySelectorAll('[data-ambiente]').forEach(function (elemento) {
        const amb = porId.get(elemento.dataset.ambiente);
        if (amb) {
            aplicarStatus(elemento, amb);
        }
    });
}

if (document.querySelector('[data-painel], [data-ambiente]')) {
    setInterval(atualizarStatus, 3000);
}

document.querySelectorAll('input[type="file"][data-max-bytes]').forEach(function (campo) {
    campo.addEventListener('change', function () {
        const arquivo = campo.files[0];
        const excedeu = arquivo && arquivo.size > Number(campo.dataset.maxBytes);
        campo.setCustomValidity(excedeu ? 'O arquivo passa do limite de 1 MB.' : '');
        campo.reportValidity();
    });
});
