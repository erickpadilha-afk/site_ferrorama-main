<?php
session_start();
require_once __DIR__ . '/config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: pagina-inicial/index.php");
    exit;
}

$f_id_trem    = intval($_GET['id_trem'] ?? 0);
$f_tipo_sensor = trim($_GET['tipo_sensor'] ?? '');

$condicoes = [];
$valores   = [];
$tipos     = '';

if ($f_id_trem > 0) {
    $condicoes[] = 'l.fk_id_trem = ?';
    $valores[]   = $f_id_trem;
    $tipos      .= 'i';
}
if ($f_tipo_sensor !== '') {
    $condicoes[] = 'l.tipo_sensor = ?';
    $valores[]   = $f_tipo_sensor;
    $tipos      .= 's';
}

$sql = "SELECT l.*, t.prefixo_trem, t.modelo_trem 
        FROM leituras l 
        INNER JOIN trens t ON l.fk_id_trem = t.id_trem";

if (!empty($condicoes)) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}

// Requisito do Guia: LIMIT 100 para não travar a aplicação
$sql .= " ORDER BY l.data_hora DESC LIMIT 100";

if (!empty($valores)) {
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $leituras = $stmt->get_result();
} else {
    $leituras = $conexao->query($sql);
}

$trens = $conexao->query("SELECT id_trem, prefixo_trem FROM trens ORDER BY prefixo_trem ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Leituras - GTT</title>
    <style>
        body { font-family: Arial, sans-serif; background: #222; color: #fff; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .filters { background: #cca424; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; color: #000; }
        .filters select, .filters button { padding: 8px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; background: #333; }
        th, td { padding: 12px; border-bottom: 1px solid #444; text-align: left; }
        th { background: #cca424; color: #000; }
        a { color: #dfd29d; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <p><a href="admin/controle.php">&larr; Voltar ao Painel</a> | <a href="simulador.php">Acessar Simulador &rarr;</a></p>
        <h1>Leituras Recebidas dos Sensores (Últimas 100)</h1>

        <form method="GET" action="leituras.php" class="filters">
            <select name="id_trem">
                <option value="0">-- Todos os Trens --</option>
                <?php while ($t = $trens->fetch_assoc()): ?>
                    <option value="<?php echo $t['id_trem']; ?>" <?php echo ($f_id_trem == $t['id_trem']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['prefixo_trem']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="tipo_sensor">
                <option value="">-- Todos os Sensores --</option>
                <option value="velocidade" <?php echo ($f_tipo_sensor === 'velocidade') ? 'selected' : ''; ?>>Velocidade</option>
                <option value="temperatura" <?php echo ($f_tipo_sensor === 'temperatura') ? 'selected' : ''; ?>>Temperatura</option>
                <option value="consumo" <?php echo ($f_tipo_sensor === 'consumo') ? 'selected' : ''; ?>>Consumo</option>
                <option value="vibracao" <?php echo ($f_tipo_sensor === 'vibracao') ? 'selected' : ''; ?>>Vibração</option>
            </select>

            <button type="submit">Filtrar</button>
            <a href="leituras.php" style="color: #000; align-self: center;">Limpar</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Trem</th>
                    <th>Sensor / Tipo</th>
                    <th>Valor Medido</th>
                    <th>Data e Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($leituras && $leituras->num_rows > 0): ?>
                    <?php while ($r = $leituras->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $r['id_leitura']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['prefixo_trem']); ?></strong> (<?php echo htmlspecialchars($r['modelo_trem']); ?>)</td>
                            <td><?php echo ucfirst($r['tipo_sensor']); ?></td>
                            <td><strong><?php echo number_format($r['valor'], 2, ',', '.'); ?> <?php echo $r['unidade']; ?></strong></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($r['data_hora'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center;">Nenhuma leitura cadastrada. Use o simulador para gerar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>