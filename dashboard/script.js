document.addEventListener('DOMContentLoaded', () => {
    // 1. Animação de entrada das barras do gráfico CSS
    const barFills = document.querySelectorAll('.chart-bar-fill');
    
    barFills.forEach(bar => {
        const targetWidth = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.transition = 'width 0.8s ease-out';
            bar.style.width = targetWidth;
        }, 100);
    });

    // 2. Atualização automática a cada 30 segundos (opcional para sensores em tempo real)
    setInterval(() => {
        // Recarrega a página de forma silenciosa para atualizar os indicadores do banco
        window.location.reload();
    }, 30000);
});