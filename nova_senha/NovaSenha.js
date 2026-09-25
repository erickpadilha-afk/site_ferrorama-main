document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('novaSenhaForm');

    if (form) {
        form.addEventListener('submit', function (e) {
            const login = document.getElementById('login').value.trim();
            const novaSenha = document.getElementById('nova_senha').value.trim();
            const confSenha = document.getElementById('conf_senha').value.trim();

            if (login === "" || novaSenha === "" || confSenha === "") {
                e.preventDefault();
                alert("Por favor, preencha todos os campos.");
                return;
            }

            if (novaSenha !== confSenha) {
                e.preventDefault();
                alert("As senhas digitadas não coincidem!");
            }
        });
    }
});