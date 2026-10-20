<?php
session_start();

require_once __DIR__ . '/../config/conexao.php';

if (!isset($conexao)) {
    @include_once __DIR__ . '/config/conexao.php';
}

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../pagina-inicial/index.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Busca o NOME real e o PAPEL diretamente da tabela usuario
$nome_exibicao = 'Usuário';
$papel = $_SESSION['papel'] ?? 'cliente';

if (isset($conexao) && $conexao) {
    try {
        $stmt_u = $conexao->prepare("SELECT nome, papel FROM usuario WHERE id = ?");
        $stmt_u->bind_param("i", $id_usuario);
        $stmt_u->execute();
        $res_u = $stmt_u->get_result();
        if ($user_data = $res_u->fetch_assoc()) {
            if (!empty($user_data['nome'])) {
                $nome_exibicao = $user_data['nome'];
            }
            $papel = $user_data['papel'];
        }
    } catch (Exception $e) {}
}

// Pega o primeiro nome para a barra
$primeiro_nome = explode(' ', trim($nome_exibicao))[0];

// =========================================================================
// CONSULTAS DOS 5 INDICADORES DO PAINEL (BLOCO 4)
// =========================================================================

// 1. Contagem de trens por status ou modelo
$trens_por_status = [];
$total_trens = 0;

if (isset($conexao) && $conexao) {
    try {
        $res_trens = $conexao->query("SELECT status, COUNT(*) AS qtd FROM trens GROUP BY status ORDER BY qtd DESC");
        while ($r = $res_trens->fetch_assoc()) {
            $trens_por_status[$r['status']] = (int)$r['qtd'];
        }
    } catch (mysqli_sql_exception $e) {
        try {
            $res_trens = $conexao->query("SELECT modelo_trem AS status, COUNT(*) AS qtd FROM trens GROUP BY modelo_trem ORDER BY qtd DESC");
            while ($r = $res_trens->fetch_assoc()) {
                $trens_por_status[$r['status']] = (int)$r['qtd'];
            }
        } catch (mysqli_sql_exception $e2) {
            $trens_por_status = [];
        }
    }
}
$total_trens = array_sum($trens_por_status);

// 2. Alertas ativos por severidade
$alertas_por_sev = [];
if (isset($conexao) && $conexao) {
    try {
        $res_alertas = $conexao->query("SELECT severidade, COUNT(*) AS qtd FROM alertas GROUP BY severidade");
        while ($r = $res_alertas->fetch_assoc()) {
            $alertas_por_sev[$r['severidade']] = (int)$r['qtd'];
        }
    } catch (mysqli_sql_exception $e) {}
}

// 3. Sensores com leitura crítica
$sensores_criticos_qtd = 0;
$tem_registro_alertas = false;

if (isset($conexao) && $conexao) {
    try {
        $res_criticos = $conexao->query("SELECT COUNT(*) AS qtd FROM alertas WHERE severidade = 'critico'");
        if ($res_criticos && $r = $res_criticos->fetch_assoc()) {
            $sensores_criticos_qtd = (int)$r['qtd'];
        }
        
        $res_check = $conexao->query("SELECT COUNT(*) AS total FROM alertas");
        if ($res_check && $r_chk = $res_check->fetch_assoc()) {
            $tem_registro_alertas = ((int)$r_chk['total']) > 0;
        }
    } catch (mysqli_sql_exception $e) {}
}

// 4. Viagens em andamento
$viagens_andamento_qtd = 0;
$tem_registro_rotas = false;

if (isset($conexao) && $conexao) {
    try {
        $res_viagens = $conexao->query("SELECT COUNT(*) AS qtd FROM rotas WHERE status = 'em andamento'");
        if ($res_viagens && $r = $res_viagens->fetch_assoc()) {
            $viagens_andamento_qtd = (int)$r['qtd'];
        }
        
        $res_chk_rotas = $conexao->query("SELECT COUNT(*) AS total FROM rotas");
        if ($res_chk_rotas && $r_rot = $res_chk_rotas->fetch_assoc()) {
            $tem_registro_rotas = ((int)$r_rot['total']) > 0;
        }
    } catch (mysqli_sql_exception $e) {}
}

// 5. Velocidade média da frota
$velocidade_media = null;

if (isset($conexao) && $conexao) {
    try {
        $res_vel = $conexao->query("SELECT ROUND(AVG(velocidade_kmh), 1) AS media FROM leitura_sensor WHERE velocidade_kmh > 0");
        if ($res_vel && $r = $res_vel->fetch_assoc()) {
            $velocidade_media = $r['media'];
        }
    } catch (mysqli_sql_exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Train Track - Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="sticky-bar">
        <div class="nav-left">
            <div class="logo-container">
                <a href="dashboard.php">
                    <img src="../images/GTT-logo.png" alt="Golden Train Track Logo" class="nav-logo" onerror="this.style.display='none'">
                </a>
            </div>
            
            <!-- MENU COM TRENS E DEMAIS ABAS DE NAVEGAÇÃO -->
            <ul class="nav-links">
                <li class="nav-item active"><a href="dashboard.php">Início</a></li>
                <li class="nav-item"><a href="Barra/trens/trens.php">Trens</a></li>
                <?php if ($papel === 'administrador'): ?>
                    <li class="nav-item"><a href="../admin/controle.php">Painel Admin</a></li>
                <?php endif; ?>
                <li class="nav-item tab-viajar"><a href="Barra/viajar/viajar.php">Viajar</a></li>
            </ul>
        </div>

        <div class="nav-right">
            <a href="Barra/usuario/usuario.php" class="user-btn">
                <span><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <svg class="user-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        
        <h1 class="welcome-title">Painel Operacional GTT</h1>

        <!-- INDICADORES REAIS DO BANCO -->
        <section class="stats-container">
            <div class="info-card">
                <span class="stat-number">
                    <?php echo $total_trens > 0 ? $total_trens : '<small class="no-data">Sem dados</small>'; ?>
                </span>
                <span class="stat-label">Total de Trens</span>
            </div>

            <div class="info-card">
                <span class="stat-number">
                    <?php 
                    if ($tem_registro_rotas) {
                        echo $viagens_andamento_qtd;
                    } else {
                        echo '<small class="no-data">Sem dados</small>';
                    }
                    ?>
                </span>
                <span class="stat-label">Viagens em Andamento</span>
            </div>

            <div class="info-card">
                <span class="stat-number">
                    <?php 
                    if ($velocidade_media !== null && $velocidade_media > 0) {
                        echo htmlspecialchars($velocidade_media) . ' <small style="font-size:18px;">km/h</small>';
                    } else {
                        echo '<small class="no-data">Sem dados</small>';
                    }
                    ?>
                </span>
                <span class="stat-label">Velocidade Média da Frota</span>
            </div>

            <?php if ($papel === 'administrador' || $papel === 'maquinista'): ?>
                <div class="info-card">
                    <span class="stat-number <?php echo $sensores_criticos_qtd > 0 ? 'text-alert' : ''; ?>">
                        <?php 
                        if ($tem_registro_alertas) {
                            echo $sensores_criticos_qtd;
                        } else {
                            echo '<small class="no-data">Sem dados</small>';
                        }
                        ?>
                    </span>
                    <span class="stat-label">Alertas Críticos</span>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($papel === 'administrador' || $papel === 'maquinista'): ?>
            <section class="chart-section">
                <h2>Distribuição da Frota por Modelo</h2>
                <?php if (!empty($trens_por_status)): ?>
                    <div class="css-chart">
                        <?php foreach ($trens_por_status as $st_nome => $st_qtd): 
                            $porcentagem = $total_trens > 0 ? round(($st_qtd / $total_trens) * 100) : 0;
                        ?>
                            <div class="chart-row">
                                <span class="chart-label"><?php echo ucfirst($st_nome); ?> (<?php echo $st_qtd; ?>)</span>
                                <div class="chart-bar-bg">
                                    <div class="chart-bar-fill" style="width: <?php echo $porcentagem; ?>%;"></div>
                                </div>
                                <span class="chart-pct"><?php echo $porcentagem; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data-msg">Sem dados de trens no banco de dados.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <h2 class="section-title">Pacotes de Viagens Disponíveis</h2>
        <section class="packages-container">
            
            <a href="Barra/viajar/viajar.php" class="pacote-card pacote-serra">
                <div class="pacote-content">
                    <h2>Pacote Serra Catarinense</h2>
                    <p class="parcelamento">a partir de 3x</p>
                    <p class="preco">R$ 60,00</p>
                </div>
            </a>

            <a href="Barra/viajar/viajar.php" class="pacote-card pacote-amazonia">
                <div class="pacote-content">
                    <h2>Pacote Grande Amazônia</h2>
                    <p class="parcelamento">a partir de 6x</p>
                    <p class="preco">R$ 50,00</p>
                    <span class="tag-oferta">*Ofertas Limitadas</span>
                </div>
            </a>

        </section>

    </main>

    <script src="script.js"></script>
</body>
</html>