<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../pagina-inicial/index.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Buscar o nome real e o papel do usuário
$nome_exibicao = 'Usuário';
if (isset($conexao) && $conexao) {
    try {
        $stmt_u = $conexao->prepare("SELECT nome FROM usuario WHERE id = ?");
        $stmt_u->bind_param("i", $id_usuario);
        $stmt_u->execute();
        $res_u = $stmt_u->get_result();
        if ($user = $res_u->fetch_assoc()) {
            if (!empty($user['nome'])) {
                $nome_exibicao = $user['nome'];
            }
        }
    } catch (Exception $e) {}
}
$primeiro_nome = explode(' ', trim($nome_exibicao))[0];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Relatórios - GTT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="sticky-bar">
        <div class="nav-left">
            <div class="logo-container">
                <a href="../dashboard/dashboard.php">
                    <img src="../images/GTT-logo.png" alt="Golden Train Track Logo" class="nav-logo" onerror="this.style.display='none'">
                </a>
            </div>
            
            <ul class="nav-links">
                <li class="nav-item active"><a href="gera_relatorio.php">Relatórios</a></li>
                <li class="nav-item tab-viajar"><a href="viagens.php">Viagens</a></li>
            </ul>
        </div>

        <div class="nav-right">
            <a href="../dashboard/Barra/usuario/usuario.php" class="user-btn">
                <span><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <svg class="user-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <h1 class="page-title">Emissão de Relatórios Operacionais</h1>

        <section class="form-section">
            <form method="GET" action="visualiza_relatorio.php" class="relatorio-form">
                
                <div class="form-group">
                    <label for="tipo">Tipo de Relatório *</label>
                    <select name="tipo" id="tipo" required>
                        <option value="desempenho">Desempenho (Velocidade por Trem)</option>
                        <option value="consumo">Consumo de Energia / Combustível</option>
                        <option value="eficiencia">Eficiência Operacional (Viagens)</option>
                        <option value="manutencao">Manutenção e Alertas</option>
                    </select>
                </div>

                <div class="form-grid-dates">
                    <div class="form-group">
                        <label for="data_inicio">Data Inicial *</label>
                        <input type="date" name="data_inicio" id="data_inicio" required value="<?php echo date('Y-m-01'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="data_fim">Data Final *</label>
                        <input type="date" name="data_fim" id="data_fim" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Gerar Relatório</button>
                </div>
            </form>
        </section>
    </main>

</body>
</html>