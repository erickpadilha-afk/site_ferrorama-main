document.addEventListener("DOMContentLoaded", () => {
    
    // Tratamento dos botões da barra de navegação
    const navItems = document.querySelectorAll(".nav-item");
    const userBtn = document.querySelector(".user-btn");

    navItems.forEach(item => {
        item.addEventListener("click", () => {
            const nomeAba = item.textContent.trim();
            
            if (nomeAba === "Início") {
                console.log("Você já está na tela inicial (Dashboard).");
            } else {
                console.log(`Navegando para a tela de: ${nomeAba}`);
            }

            navItems.forEach(i => i.classList.remove("active"));
            item.classList.add("active");
        });
    });

    if (userBtn) {
        userBtn.addEventListener("click", () => {
            console.log("Navegando para o perfil do Usuário.");
        });
    }

    // Tratamento de cliques nos pacotes
    const pacotes = document.querySelectorAll(".pacote-card");

    pacotes.forEach(pacote => {
        pacote.addEventListener("click", () => {
            const tituloPacote = pacote.querySelector("h2").textContent;
            console.log(`Pacote selecionado: ${tituloPacote}. Redirecionando...`);
        });
    });

});