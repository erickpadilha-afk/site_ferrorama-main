document.addEventListener('DOMContentLoaded', () => {
    const cadastroForm = document.getElementById('loginform');

    if (cadastroForm) {
        cadastroForm.addEventListener('submit', function (e) {
            const nome = document.getElementById('nome').value.trim();
            const login = document.getElementById('login').value.trim();
            const senha = document.getElementById('senha').value.trim();
            const papel = document.getElementById('papel').value.trim();

            if (nome === "" || login === "" || senha === "" || papel === "") {
                e.preventDefault();
                alert("Por favor, preencha todos os campos obrigatórios.");
            }
        });
    }
});