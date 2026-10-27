// JavaScript da interface. O auto-refresh do painel (X4) e do output (X5) entra aqui.

// Pede confirmação antes de enviar formulários marcados com data-confirmar.
document.addEventListener('submit', function (ev) {
    const msg = ev.target.dataset.confirmar;
    if (msg && !confirm(msg)) {
        ev.preventDefault();
    }
});

// Scripts: alterna entre enviar arquivo e colar texto. O bloco escondido fica
// desabilitado para não ser validado nem enviado no POST.
function mostrarOrigem(valor) {
    document.querySelectorAll('[data-origem]').forEach(function (bloco) {
        const ativo = bloco.dataset.origem === valor;
        bloco.hidden = !ativo;
        bloco.querySelectorAll('input, textarea').forEach(function (campo) {
            campo.disabled = !ativo;
        });
    });
}

document.querySelectorAll('input[name="origem"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        mostrarOrigem(radio.value);
    });
    // O navegador pode restaurar a opção marcada ao voltar para a página.
    if (radio.checked) {
        mostrarOrigem(radio.value);
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
