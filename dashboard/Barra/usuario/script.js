document.addEventListener("DOMContentLoaded", () => {
    const logoutBtn = document.getElementById("logoutBtn");

    if (logoutBtn) {
        logoutBtn.addEventListener("click", (e) => {
            // Exibe a caixa de alerta nativa de confirmação
            const confirmacao = confirm("Você tem certeza que deseja sair?\nSua sessão será cancelada.");
            
            // Se o usuário clicar em Cancelar, desfaz o clique
            if (!confirmacao) {
                e.preventDefault();
            }
        });
    }
});