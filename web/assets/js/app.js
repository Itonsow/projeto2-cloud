// Pede confirmação antes de enviar formulários marcados com data-confirmar.
document.addEventListener('submit', function (ev) {
    const msg = ev.target.dataset.confirmar;
    if (msg && !confirm(msg)) {
        ev.preventDefault();
    }
});

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
    if (radio.checked) {
        mostrarOpcao(radio.name, radio.value);
    }
});

const ROTULOS_STATUS = JSON.parse(document.body.dataset.statusRotulos || '{}');

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

const output = document.getElementById('output');

async function atualizarOutput() {
    let resposta;
    let texto;
    try {
        resposta = await fetch(output.dataset.url, { cache: 'no-store' });
        if (resposta.status !== 404 && !resposta.ok) {
            return;
        }
        texto = resposta.status === 404 ? '' : await resposta.text();
    } catch (erro) {
        return;
    }

    const novo = texto || '(sem output ainda)';
    if (novo !== output.textContent) {
        output.textContent = novo;
        if (document.getElementById('acompanhar').checked) {
            output.scrollTop = output.scrollHeight;
        }
    }
    document.getElementById('output-truncado').hidden = resposta.headers.get('X-Output-Truncado') !== '1';
}

if (output) {
    atualizarOutput();
    setInterval(atualizarOutput, 2000);
}

document.querySelectorAll('input[type="file"][data-max-bytes]').forEach(function (campo) {
    campo.addEventListener('change', function () {
        const arquivo = campo.files[0];
        const excedeu = arquivo && arquivo.size > Number(campo.dataset.maxBytes);
        campo.setCustomValidity(excedeu ? 'O arquivo passa do limite de 1 MB.' : '');
        campo.reportValidity();
    });
});
