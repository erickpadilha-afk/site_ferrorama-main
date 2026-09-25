document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginform');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value.trim();

            if (email === "" || senha === "") {
                e.preventDefault();
                alert("Por favor, preencha os campos de Email e Senha.");
            }
        });
    }
});