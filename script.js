document.getElementById('loginform').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const senha = document.getElementById('senha').value;
    if (email && senha) {
        window.location.href = "dashboard.html";
    }
    e.preventDefault();
});