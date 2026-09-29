<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GTT - Relatório</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="navbar">
        <div class="nav-left">
            <div class="logo-box">
                <img src="../../GTT-logo.png" alt="GTT Logo" class="logo-img">
            </div>
            <nav class="nav-tabs">
                <a href="#" class="tab-item active">Relatório</a>
                <a href="#" class="tab-item tab-viagens">Viagens</a>
            </nav>
        </div>
        <div class="nav-right">
            <span class="user-text">Usuario</span>
            <div class="user-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
        </div>
    </header>

    <main class="dashboard-wrapper">
        
        <div class="driver-container">
            <img src="images/condutor_exemplo.png" alt="Condutor do Trem" class="driver-image">
        </div>

        <div class="controls-container">
            
            <div class="form-section">
                <h2 class="section-title">Selecione o período</h2>
                <div class="yellow-panel date-panel">
                    <div class="date-field">
                        <label class="date-label">De:</label>
                        <div class="input-container">
                            <input type="date" value="2025-05-01">
                        </div>
                    </div>
                    
                    <div class="date-field">
                        <label class="date-label">Até:</label>
                        <div class="input-container">
                            <input type="date" value="2025-05-31">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Selecione o tipo de relatório</h2>
                <div class="yellow-panel options-panel">
                    
                    <div class="report-card active" onclick="selecionarCard(this)">
                        <div class="card-icon-wrapper">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2c-4.42 0-8 .5-8 4v10.5C4 18.43 5.57 20 7.5 20L6 21v1h12v-1l-1.5-1c1.93 0 3.5-1.57 3.5-3.5V6c0-3.5-3.58-4-8-4zm0 2c3.71 0 6 .3 6 2H6c0-1.7 2.29-2 6-2zm5 11H7v-5h10v5zm-1.5 2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm-7 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                            </svg>
                        </div>
                        <span class="card-label">Por trem</span>
                    </div>

                    <div class="report-card" onclick="selecionarCard(this)">
                        <div class="card-icon-wrapper">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm0-12c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 6c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                            </svg>
                        </div>
                        <span class="card-label">Por sensor</span>
                    </div>

                    <div class="report-card" onclick="selecionarCard(this)">
                        <div class="card-icon-wrapper">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                            </svg>
                        </div>
                        <span class="card-label">Por ocorrência</span>
                    </div>

                </div>
            </div>

            <div class="action-row">
                <button class="btn-submit" onclick="gerarRelatorio()">Gerar relatório</button>
            </div>

        </div>
    </main>

    <script>
        function selecionarCard(elemento) {
            document.querySelectorAll('.report-card').forEach(card => {
                card.classList.remove('active');
            });
            elemento.classList.add('active');
        }

        function gerarRelatorio() {
            window.location.href = "resultado.html"; 
        }
    </script>
</body>
</html>