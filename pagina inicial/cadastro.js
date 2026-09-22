document.getElementById('loginform').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const senha = document.getElementById('senha').value.trim();
    const data_nasc = document.getElementById('data_nasc').value.trim();

    if (email !== "" && senha !== "" && data_nasc !== "") {
        window.location.href = "../dashboard/dashboard.html";
    } else {
        window.alert("Por favor, preencha todos os campos.");
    }
});