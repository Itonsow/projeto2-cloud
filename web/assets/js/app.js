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

// Recusa no navegador arquivos maiores que data-max-bytes (o servidor também valida).
document.querySelectorAll('input[type="file"][data-max-bytes]').forEach(function (campo) {
    campo.addEventListener('change', function () {
        const arquivo = campo.files[0];
        const excedeu = arquivo && arquivo.size > Number(campo.dataset.maxBytes);
        campo.setCustomValidity(excedeu ? 'O arquivo passa do limite de 1 MB.' : '');
        campo.reportValidity();
    });
});
