<?php
session_start();
require_once __DIR__ . '/config/conexao.php';

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

// Consulta de 4 tabelas encadeadas
$sql = "SELECT a.*, l.valor, l.unidade, t.prefixo_trem, t.modelo_trem 
        FROM alertas a 
        INNER JOIN leituras l ON a.id_leitura = l.id_leitura 
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

$primeiro_nome = explode(' ', trim($_SESSION['nome'] ?? 'Admin'))[0];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Central de Alertas - GTT</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { background-color: #222; color: #fff; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .top-nav { margin-bottom: 20px; font-size: 16px; }
        .top-nav a { color: #dfd29d; text-decoration: none; font-weight: bold; }
        
        .filters { background: #cca424; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; color: #000; align-items: center; }
        .filters select, .filters button { padding: 8px 12px; border-radius: 4px; border: 1px solid #888; font-weight: bold; }
        .filters button { background: #000; color: #cca424; border: none; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; background: #2a2a2a; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #3d3d3d; }
        th { background: #cca424; color: #000; }

        /* Cores semânticas para a Severidade */
        .badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; text-transform: uppercase; display: inline-block; }
        .badge-normal { background: #2e7d32; color: #fff; }
        .badge-atencao { background: #f57c00; color: #fff; }
        .badge-critico { background: #d32f2f; color: #fff; font-size: 14px; box-shadow: 0 0 8px rgba(211, 47, 47, 0.8); }

        /* Destaque visual para linhas críticas */
        tr.linha-critica { background-color: rgba(211, 47, 47, 0.15); border-left: 5px solid #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <a href="admin/controle.php">&larr; Voltar ao Painel</a>
        </div>

        <h1 style="color: #dfd29d; margin-bottom: 20px;">Central de Alertas e Notificações</h1>

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
            <a href="notificacoes_alertas.php" style="color: #000; text-decoration: none; font-weight: bold;">Limpar</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Trem de Origem</th>
                    <th>Tipo</th>
                    <th>Severidade</th>
                    <th>Mensagem do Alerta</th>
                    <th>Valor Registrado</th>
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
                                <span class="badge badge-<?php echo $row['severidade']; ?>">
                                    <?php echo ucfirst($row['severidade']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['mensagem']); ?></td>
                            <td><strong><?php echo number_format($row['valor'], 2, ',', '.'); ?> <?php echo $row['unidade']; ?></strong></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_hora'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; color: #aaa; padding: 20px;">Nenhum alerta encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>