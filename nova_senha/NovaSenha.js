document.addEventListener('DOMContentLoaded', () => {
    // Reset dos formulários para garantir que entrem limpos
    const formEtapa1 = document.getElementById('formEtapa1');
    if (formEtapa1) {
        formEtapa1.reset();
        formEtapa1.addEventListener('submit', function (e) {
            const emailInput = document.getElementById('email').value.trim();
            if (emailInput === "") {
                e.preventDefault();
                alert("Por favor, digite seu e-mail cadastrado.");
            }
        });
    }

    const formEtapa2 = document.getElementById('formEtapa2');
    if (formEtapa2) {
        formEtapa2.reset();
        formEtapa2.addEventListener('submit', function (e) {
            const codigo = document.getElementById('codigo').value.trim();
            const novaSenha = document.getElementById('nova_senha').value.trim();
            const confSenha = document.getElementById('confirma_senha').value.trim();

            if (codigo === "" || novaSenha === "" || confSenha === "") {
                e.preventDefault();
                alert("Por favor, preencha todos os campos.");
                return;
            }

            if (codigo.length !== 6) {
                e.preventDefault();
                alert("O código deve ter exatamente 6 dígitos.");
                return;
            }

            if (novaSenha !== confSenha) {
                e.preventDefault();
                alert("A nova senha e a confirmação não coincidem!");
            }
        });
    }
});