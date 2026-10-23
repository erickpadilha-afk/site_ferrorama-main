<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Bloqueio de segurança: Apenas usuários logados
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../pagina-inicial/index.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$papel      = $_SESSION['papel'] ?? 'cliente';

// Busca nome do usuário
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

// Data atual para listar viagens do dia
$data_hoje = date('Y-m-d');
$f_data    = trim($_GET['data_viagem'] ?? $data_hoje);

// Consulta as rotas cadastradas para o dia selecionado
$viagens = [];
if (isset($conexao) && $conexao) {
    try {
        $sql = "SELECT r.*, t.prefixo_trem, t.modelo_trem 
                FROM rotas r 
                INNER JOIN trens t ON r.id_trem = t.id_trem 
                WHERE r.data_viagem = ? 
                ORDER BY r.hora_partida ASC";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("s", $f_data);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $viagens[] = $row;
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escala de Viagens - GTT</title>
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
        <h1 class="page-title">Escala de Viagens e Rotas do Dia</h1>

        <!-- FILTRO POR DATA -->
        <section class="search-section" style="margin-bottom: 25px;">
            <form method="GET" action="viagens.php" class="filter-form" style="display: flex; gap: 15px; align-items: center; background: #cca424; padding: 15px; border-radius: 12px; color: #000;">
                <label for="data_viagem" style="font-weight: bold;">Selecionar Data:</label>
                <input type="date" name="data_viagem" id="data_viagem" value="<?php echo htmlspecialchars($f_data); ?>" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #888;">
                <button type="submit" class="btn-submit" style="width: auto; padding: 8px 20px;">Filtrar</button>
                <?php if ($f_data !== $data_hoje): ?>
                    <a href="viagens.php" style="color: #000; font-weight: bold; text-decoration: none;">Hoje</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- TABELA DE ESCALA DE VIAGENS -->
        <section class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trem</th>
                        <th>Origem &rarr; Destino</th>
                        <th>Horário Partida / Chegada</th>
                        <th>Status da Viagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($viagens)): ?>
                        <?php foreach ($viagens as $v): ?>
                            <tr>
                                <td>#<?php echo $v['id_rota']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($v['prefixo_trem']); ?></strong> 
                                    <small style="color: #aaa;">(<?php echo htmlspecialchars($v['modelo_trem']); ?>)</small>
                                </td>
                                <td><?php echo htmlspecialchars($v['origem']) . ' &rarr; ' . htmlspecialchars($v['destino']); ?></td>
                                <td>
                                    <?php 
                                    echo substr($v['hora_partida'], 0, 5); 
                                    if (!empty($v['hora_chegada'])) {
                                        echo ' / ' . substr($v['hora_chegada'], 0, 5);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo str_replace(' ', '-', strtolower($v['status'])); ?>">
                                        <?php echo ucfirst($v['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-table">
                                Nenhuma viagem agendada para a data selecionada (<?php echo date('d/m/Y', strtotime($f_data)); ?>).
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>