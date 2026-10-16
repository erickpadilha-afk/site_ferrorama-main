<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Bloqueia acesso de utilizadores não autenticados ou não administradores
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../pagina-inicial/index.php");
    exit;
}

if (isset($_SESSION['papel']) && $_SESSION['papel'] !== 'administrador') {
    header("Location: ../dashboard/dashboard.php");
    exit;
}

// Obter nome do utilizador logado
if (isset($_SESSION['nome']) && !empty($_SESSION['nome'])) {
    $nome_completo = $_SESSION['nome'];
} else {
    $id_usuario = $_SESSION['id_usuario'];
    $stmt = $conexao->prepare("SELECT nome FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($user = $res->fetch_assoc()) {
        $nome_completo = $user['nome'];
    } else {
        $nome_completo = $_SESSION['login'] ?? 'Usuário';
    }
}

$partes_nome = explode(' ', trim($nome_completo));
$primeiro_nome = $partes_nome[0];

// ==========================================
// CONSULTAS DINÂMICAS AO BANCO DE DADOS
// ==========================================

// 1. Velocidade Média Atual (da última leitura registrada)
$res_vel = $conexao->query("SELECT AVG(velocidade_kmh) as vel_media FROM leitura_sensor WHERE data_hora >= NOW() - INTERVAL 1 DAY");
$row_vel = $res_vel ? $res_vel->fetch_assoc() : null;
$velocidade_media = !empty($row_vel['vel_media']) ? number_format($row_vel['vel_media'], 1, ',', '.') : '0.0';

// 2. Contagem de Trens por Situação/Status
$res_trens = $conexao->query("SELECT situacao_trem, COUNT(*) as qtd FROM trens GROUP BY situacao_trem");
$qtd_ativos = 0;
$qtd_manutencao = 0;
$qtd_parados = 0;

if ($res_trens) {
    while ($t = $res_trens->fetch_assoc()) {
        if ($t['situacao_trem'] === 'ativo') $qtd_ativos = $t['qtd'];
        elseif ($t['situacao_trem'] === 'manutencao') $qtd_manutencao = $t['qtd'];
        elseif ($t['situacao_trem'] === 'inativo') $qtd_parados = $t['qtd'];
    }
}

// 3. Contagem Total de Utilizadores/Passageiros
$res_user = $conexao->query("SELECT COUNT(*) as total_users FROM usuario");
$total_passageiros = $res_user ? $res_user->fetch_assoc()['total_users'] : 0;

// 4. Painel de Alertas Ativos
$res_total_alertas = $conexao->query("SELECT COUNT(*) as total FROM alertas");
$total_alertas = $res_total_alertas ? $res_total_alertas->fetch_assoc()['total'] : 0;

$res_criticos = $conexao->query("SELECT COUNT(*) as total FROM alertas WHERE severidade = 'critico'");
$qtd_criticos = $res_criticos ? $res_criticos->fetch_assoc()['total'] : 0;

$res_avisos = $conexao->query("SELECT COUNT(*) as total FROM alertas WHERE severidade = 'atencao'");
$qtd_avisos = $res_avisos ? $res_avisos->fetch_assoc()['total'] : 0;

// Buscar os 4 alertas mais recentes
$res_lista_alertas = $conexao->query("
    SELECT a.*, t.prefixo_trem 
    FROM alertas a
    INNER JOIN leitura_sensor l ON a.id_leitura = l.id_leitura
    INNER JOIN trens t ON l.fk_id_trem = t.id_trem
    ORDER BY a.data_hora DESC LIMIT 4
");

// 5. Painel de Sensores/Leituras Críticas
$res_total_sensores_criticos = $conexao->query("SELECT COUNT(*) as total FROM leitura_sensor WHERE temperatura_motor_c > 90 OR velocidade_kmh > 80");
$qtd_leituras_criticas = $res_total_sensores_criticos ? $res_total_sensores_criticos->fetch_assoc()['total'] : 0;

// Buscar as 4 leituras críticas mais recentes
$res_lista_criticas = $conexao->query("
    SELECT l.*, t.prefixo_trem 
    FROM leitura_sensor l
    INNER JOIN trens t ON l.fk_id_trem = t.id_trem
    WHERE l.temperatura_motor_c > 90 OR l.velocidade_kmh > 80
    ORDER BY l.data_hora DESC LIMIT 4
");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Administrador - GTT</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <nav class="sticky-bar">
        <div class="nav-left">
            <div class="logo-container">
                <a href="admin.php">
                    <img src="../images/GTT-logo.png" alt="Golden Train Track Logo" class="nav-logo" onerror="this.style.display='none'">
                </a>
            </div>
            
            <ul class="nav-links">
                <li class="nav-item active"><a href="admin.php">Análise</a></li>
                <li class="nav-item tab-controle"><a href="controle.php">Controle</a></li>
            </ul>
        </div>

        <div class="nav-right">
            <a href="admin_usuario.php" class="user-btn">
                <span><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <svg class="user-icon" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        
        <h1 class="page-title">Análise recente</h1>

        <!-- Banner com Estatísticas Dinâmicas do Banco -->
        <section class="stats-banner">
            <div class="stat-box">
                <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
                <span class="stat-num"><?php echo $velocidade_media; ?> Km/H</span>
                <span class="stat-desc">Velocidade Média</span>
            </div>

            <div class="stat-box">
                <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="20" height="10" rx="2"/>
                    <circle cx="7" cy="18" r="2"/>
                    <circle cx="17" cy="18" r="2"/>
                </svg>
                <span class="stat-num"><?php echo number_format($qtd_ativos, 0, ',', '.'); ?></span>
                <span class="stat-desc">Trens ativos</span>
            </div>

            <div class="stat-box">
                <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                <span class="stat-num"><?php echo number_format($qtd_manutencao, 0, ',', '.'); ?></span>
                <span class="stat-desc">Trens em manutenção</span>
            </div>

            <div class="stat-box">
                <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v5a7 7 0 0 0 7 7h1a6 6 0 0 0 6-6V11z"/>
                </svg>
                <span class="stat-num"><?php echo number_format($qtd_parados, 0, ',', '.'); ?></span>
                <span class="stat-desc">Trens parados</span>
            </div>

            <div class="stat-box">
                <svg class="stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span class="stat-num"><?php echo number_format($total_passageiros, 0, ',', '.'); ?></span>
                <span class="stat-desc">Quant. clientes</span>
            </div>
        </section>

        <!-- Grade Dinâmica com Listagem em Tempo Real -->
        <section class="dashboard-grid">
            
            <!-- Painel de Alertas Ativos -->
            <div class="card-panel">
                <h2 class="panel-header">Alertas Ativos</h2>
                
                <div class="alert-summary">
                    <span class="alert-big-number"><?php echo $total_alertas; ?></span>
                    <svg class="warning-icon" viewBox="0 0 24 24" fill="none" stroke="red" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <div class="alert-labels">
                        <span><?php echo $qtd_criticos; ?> Críticos</span>
                        <span><?php echo $qtd_avisos; ?> Avisos</span>
                    </div>
                </div>

                <ul class="alert-list">
                    <?php if ($res_lista_alertas && $res_lista_alertas->num_rows > 0): ?>
                        <?php while ($alerta = $res_lista_alertas->fetch_assoc()): ?>
                            <li>#<?php echo htmlspecialchars($alerta['prefixo_trem']); ?>: <?php echo htmlspecialchars($alerta['mensagem']); ?></li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>Nenhum alerta ativo registrado.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Painel de Sensores Críticos -->
            <div class="card-panel">
                <h2 class="panel-header">Sensores críticos</h2>
                
                <div class="alert-summary">
                    <span class="alert-big-number"><?php echo $qtd_leituras_criticas; ?></span>
                    <span class="critical-text">Leituras críticas</span>
                </div>

                <ul class="alert-list">
                    <?php if ($res_lista_criticas && $res_lista_criticas->num_rows > 0): ?>
                        <?php while ($critica = $res_lista_criticas->fetch_assoc()): ?>
                            <li>
                                #<?php echo htmlspecialchars($critica['prefixo_trem']); ?>: 
                                <?php 
                                    if ($critica['temperatura_motor_c'] > 90) {
                                        echo "Temperatura " . number_format($critica['temperatura_motor_c'], 1, ',', '.') . "°C";
                                    } else {
                                        echo "Velocidade " . number_format($critica['velocidade_kmh'], 1, ',', '.') . " Km/h";
                                    }
                                ?>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>Nenhuma leitura crítica recente.</li>
                    <?php endif; ?>
                </ul>
            </div>

        </section>

    </main>

    <script src="admin.js"></script>
</body>
</html>