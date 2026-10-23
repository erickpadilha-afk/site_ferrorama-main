<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../pagina-inicial/index.php");
    exit;
}

$tipo        = trim($_GET['tipo'] ?? 'desempenho');
$data_inicio = trim($_GET['data_inicio'] ?? date('Y-m-01'));
$data_fim    = trim($_GET['data_fim'] ?? date('Y-m-d'));
$exportar    = isset($_GET['exportar']) && $_GET['exportar'] === 'csv';

// Ajustar data fim para abranger até 23:59:59
$data_fim_busca = $data_fim . ' 23:59:59';

$dados = [];
$titulo_relatorio = "Relatório Operacional";

if ($tipo === 'desempenho') {
    $titulo_relatorio = "Relatório de Desempenho (Velocidade Média e Máxima)";
    $sql = "SELECT t.prefixo_trem AS 'Prefixo', t.modelo_trem AS 'Modelo', 
                   ROUND(AVG(l.velocidade_kmh), 1) AS 'Velocidade Média (km/h)', 
                   MAX(l.velocidade_kmh) AS 'Velocidade Máxima (km/h)' 
            FROM leitura_sensor l 
            INNER JOIN trens t ON l.fk_id_trem = t.id_trem 
            WHERE l.data_hora BETWEEN ? AND ? 
            GROUP BY t.id_trem";
} elseif ($tipo === 'consumo') {
    $titulo_relatorio = "Relatório de Consumo de Energia / Combustível";
    $sql = "SELECT t.prefixo_trem AS 'Prefixo', t.modelo_trem AS 'Modelo', 
                   ROUND(SUM(l.consumo_litros_hora), 1) AS 'Consumo Total (L)', 
                   ROUND(AVG(l.consumo_litros_hora), 1) AS 'Consumo Médio (L/h)' 
            FROM leitura_sensor l 
            INNER JOIN trens t ON l.fk_id_trem = t.id_trem 
            WHERE l.data_hora BETWEEN ? AND ? 
            GROUP BY t.id_trem";
} elseif ($tipo === 'eficiencia') {
    $titulo_relatorio = "Relatório de Eficiência Operacional (Status das Viagens)";
    $sql = "SELECT t.prefixo_trem AS 'Prefixo', r.status AS 'Status', COUNT(*) AS 'Quantidade de Viagens' 
            FROM rotas r 
            INNER JOIN trens t ON r.id_trem = t.id_trem 
            WHERE r.data_viagem BETWEEN ? AND ? 
            GROUP BY t.id_trem, r.status";
} else {
    $titulo_relatorio = "Relatório de Manutenção e Ocorrências de Alertas";
    $sql = "SELECT t.prefixo_trem AS 'Prefixo', a.severidade AS 'Severidade', COUNT(*) AS 'Total de Alertas' 
            FROM alertas a 
            INNER JOIN leitura_sensor l ON a.id_leitura = l.id_leitura 
            INNER JOIN trens t ON l.fk_id_trem = t.id_trem 
            WHERE a.data_hora BETWEEN ? AND ? 
            GROUP BY t.id_trem, a.severidade";
}

if (isset($conexao) && $conexao) {
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("ss", $data_inicio, $data_fim_busca);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $dados[] = $row;
        }
    } catch (Exception $e) {}
}

// --- EXPORTAÇÃO CSV ---[cite: 9]
if ($exportar) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_' . $tipo . '.csv');
    
    $out = fopen('php://output', 'w');
    // Adicionar BOM UTF-8 para correta acentuação no Excel[cite: 14]
    fputs($out, "\xEF\xBB\xBF");

    if (!empty($dados)) {
        fputcsv($out, array_keys($dados[0]), ';');
        foreach ($dados as $linha) {
            fputcsv($out, $linha, ';');
        }
    }
    fclose($out);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
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

// Montar URL limpa para o botão de exportar
$query_params = $_GET;
$query_params['exportar'] = 'csv';
$url_exportar = 'visualiza_relatorio.php?' . http_build_query($query_params);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Relatório - GTT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="sticky-bar no-print">
        <div class="nav-left">
            <div class="logo-container">
                <a href="../dashboard/dashboard.php">
                    <img src="../images/GTT-logo.png" alt="Logo" class="nav-logo" onerror="this.style.display='none'">
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
        <div class="top-actions no-print">
            <a href="gera_relatorio.php" class="btn-voltar">&larr; Voltar aos Filtros</a>
            <div class="right-buttons">
                <a href="<?php echo htmlspecialchars($url_exportar); ?>" class="btn-export">Exportar CSV</a>
                <button onclick="window.print();" class="btn-print">Imprimir</button>
            </div>
        </div>

        <section class="report-header">
            <h1><?php echo htmlspecialchars($titulo_relatorio); ?></h1>
            <p><strong>Período Selecionado:</strong> <?php echo date('d/m/Y', strtotime($data_inicio)); ?> até <?php echo date('d/m/Y', strtotime($data_fim)); ?></p>
        </section>

        <section class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php if (!empty($dados)): ?>
                            <?php foreach (array_keys($dados[0]) as $col): ?>
                                <th><?php echo htmlspecialchars($col); ?></th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th>Resultado</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dados)): ?>
                        <?php foreach ($dados as $row): ?>
                            <tr>
                                <?php foreach ($row as $val): ?>
                                    <td><?php echo htmlspecialchars($val ?? '-'); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td class="empty-table">Nenhum dado encontrado para o período selecionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>