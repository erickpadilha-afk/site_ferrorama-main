<?php
session_start();
require_once __DIR__ . '/config/conexao.php';

// Bloqueio de acesso para usuários não autenticados
if (!isset($_SESSION['id_usuario'])) {
    header("Location: pagina-inicial/index.php");
    exit;
}

// Filtros da Central de Alertas (Severidade e Tipo)
$f_severidade = trim($_GET['severidade'] ?? '');
$f_tipo       = trim($_GET['tipo'] ?? '');

$condicoes = [];
$valores   = [];
$tipos     = '';

if ($f_severidade !== '') {
    $condicoes[] = 'a.severidade = ?';
    $valores[]   = $f_severidade;
    $tipos      .= 's';
}
if ($f_tipo !== '') {
    $condicoes[] = 'a.tipo = ?';
    $valores[]   = $f_tipo;
    $tipos      .= 's';
}

// Consulta ajustada ligando alertas -> leitura_sensor -> trens
$sql = "SELECT a.*, l.velocidade_kmh, l.temperatura_motor_c, l.consumo_litros_hora, l.vibracao_mm_s, t.prefixo_trem, t.modelo_trem 
        FROM alertas a 
        INNER JOIN leitura_sensor l ON a.id_leitura = l.id_leitura 
        INNER JOIN trens t ON l.fk_id_trem = t.id_trem";

if (!empty($condicoes)) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}

$sql .= " ORDER BY a.data_hora DESC LIMIT 100";

if (!empty($valores)) {
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $alertas = $stmt->get_result();
} else {
    $alertas = $conexao->query($sql);
}

$nome_completo = $_SESSION['nome'] ?? $_SESSION['login'] ?? 'Admin';
$primeiro_nome = explode(' ', trim($nome_completo))[0];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Alertas - GTT</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { background-color: #222; color: #fff; min-height: 100vh; }

        /* --- NAVBAR / HEADER --- */
        .sticky-bar { position: sticky; top: 0; z-index: 1000; background-color: #cca424; display: flex; justify-content: space-between; align-items: center; height: 60px; width: 100%; }
        .nav-left { display: flex; align-items: center; height: 100%; }
        .logo-container { padding: 0 20px; border-right: 1px solid #a8861c; height: 100%; display: flex; align-items: center; }
        .logo-container img { height: 45px; }
        .nav-links { display: flex; list-style: none; height: 100%; }
        .nav-item { display: flex; align-items: center; padding: 0 35px; border-right: 1px solid #a8861c; height: 100%; }
        .nav-item a { text-decoration: none; color: #ffffff; font-size: 20px; }
        .nav-item.active { background-color: #917519; }
        .nav-right { display: flex; align-items: center; height: 100%; }
        .user-btn { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #ffffff; font-size: 20px; padding: 0 25px; height: 100%; }
        .user-icon { width: 24px; height: 24px; fill: #000; }

        /* --- MAIN CONTAINER --- */
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .top-nav { margin-bottom: 20px; font-size: 16px; }
        .top-nav a { color: #dfd29d; text-decoration: none; font-weight: bold; }
        .page-title { color: #dfd29d; margin-bottom: 25px; font-size: 28px; text-align: center; }
        
        /* --- FILTROS --- */
        .filters { background: #cca424; padding: 20px; border-radius: 15px; margin-bottom: 30px; display: flex; gap: 15px; color: #000; align-items: center; flex-wrap: wrap; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .filters label { font-weight: bold; }
        .filters select { padding: 10px; border-radius: 6px; border: 1px solid #888; font-size: 15px; }
        .filters button { background: #1a1a1a; color: #cca424; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; }
        .filters button:hover { background: #333; }
        .btn-limpar { background: #555; color: #fff; text-decoration: none; padding: 10px 15px; border-radius: 6px; font-weight: bold; font-size: 15px; }

        /* --- TABELA --- */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: #2a2a2a; border-radius: 10px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #3d3d3d; }
        th { background: #cca424; color: #000; font-weight: bold; }

        /* BADGES */
        .badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; text-transform: uppercase; display: inline-block; }
        .badge-atencao { background: #f57c00; color: #fff; }
        .badge-critico { background: #c62828; color: #fff; font-size: 13px; box-shadow: 0 0 8px rgba(198, 40, 40, 0.8); }

        /* DESTAQUE DE LINHA CRÍTICA */
        tr.linha-critica { background-color: rgba(198, 40, 40, 0.15); border-left: 5px solid #c62828; }
    </style>
</head>
<body>

    <nav class="sticky-bar">
        <div class="nav-left">
            <div class="logo-container">
                <a href="admin/admin.php">
                    <img src="images/GTT-logo.png" alt="Logo" class="nav-logo" onerror="this.style.display='none'">
                </a>
            </div>
            <ul class="nav-links">
                <li class="nav-item"><a href="admin/admin.php">Análise</a></li>
                <li class="nav-item active"><a href="admin/controle.php">Controle</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <a href="admin/admin_usuario.php" class="user-btn">
                <span><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <svg class="user-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="top-nav">
            <a href="admin/controle.php">&larr; Voltar ao Painel</a>
        </div>

        <h1 class="page-title">Central de Alertas e Notificações</h1>

        <form method="GET" action="notificacoes_alertas.php" class="filters">
            <label>Severidade:</label>
            <select name="severidade">
                <option value="">-- Todas --</option>
                <option value="atencao" <?php echo ($f_severidade === 'atencao') ? 'selected' : ''; ?>>Atenção</option>
                <option value="critico" <?php echo ($f_severidade === 'critico') ? 'selected' : ''; ?>>Crítico</option>
            </select>

            <label>Tipo de Sensor:</label>
            <select name="tipo">
                <option value="">-- Todos --</option>
                <option value="velocidade" <?php echo ($f_tipo === 'velocidade') ? 'selected' : ''; ?>>Velocidade</option>
                <option value="temperatura" <?php echo ($f_tipo === 'temperatura') ? 'selected' : ''; ?>>Temperatura</option>
                <option value="consumo" <?php echo ($f_tipo === 'consumo') ? 'selected' : ''; ?>>Consumo</option>
                <option value="vibracao" <?php echo ($f_tipo === 'vibracao') ? 'selected' : ''; ?>>Vibração</option>
            </select>

            <button type="submit">Filtrar</button>
            <a href="notificacoes_alertas.php" class="btn-limpar">Limpar</a>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trem de Origem</th>
                        <th>Tipo</th>
                        <th>Severidade</th>
                        <th>Mensagem do Alerta</th>
                        <th>Data / Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($alertas && $alertas->num_rows > 0): ?>
                        <?php while ($row = $alertas->fetch_assoc()): ?>
                            <tr class="<?php echo ($row['severidade'] === 'critico') ? 'linha-critica' : ''; ?>">
                                <td>#<?php echo $row['id_alerta']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['prefixo_trem']); ?></strong> <small>(<?php echo htmlspecialchars($row['modelo_trem']); ?>)</small></td>
                                <td><?php echo ucfirst($row['tipo']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($row['severidade']); ?>">
                                        <?php echo ucfirst($row['severidade']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['mensagem']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_hora'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #aaa; padding: 20px;">Nenhum alerta encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>