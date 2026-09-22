document.getElementById('loginform').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const senha = document.getElementById('senha').value.trim();

    if (email !== "" && senha !== "") {
        window.location.href = "../dashboard/dashboard.html";
    } else {
        window.alert("Por favor, preencha os campos de Email e Senha");
    }
});