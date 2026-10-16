<?php
session_start();
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/limites.php';

// Verificação de autenticação
if (!isset($_SESSION['id_usuario'])) {
    header("Location: pagina-inicial/index.php");
    exit;
}

$mensagem = "";
$tipo_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar'])) {
    $fk_id_trem = intval($_POST['fk_id_trem'] ?? 0);
    $tipo_sensor = $_POST['tipo_sensor'] ?? 'velocidade';
    $quantidade  = intval($_POST['quantidade'] ?? 10);
    $gerar_anomalias = isset($_POST['gerar_anomalias']);

    if ($fk_id_trem <= 0) {
        $mensagem = "Selecione um trem válido!";
        $tipo_msg = "erro";
    } else {
        // Unidades por tipo
        $unidades = [
            'velocidade'  => 'km/h',
            'temperatura' => '°C',
            'consumo'     => 'L/h',
            'vibracao'    => 'mm/s'
        ];
        $unidade = $unidades[$tipo_sensor] ?? 'un';
        $limite_base = LIMITES_SENSORES[$tipo_sensor] ?? 50.00;

        // CONSULTAS PREPARADAS FORA DO LAÇO (Requisito do Guia)
        $stmt_leitura = $conexao->prepare("INSERT INTO leituras (fk_id_trem, tipo_sensor, valor, unidade, data_hora) VALUES (?, ?, ?, ?, ?)");
        $stmt_alerta  = $conexao->prepare("INSERT INTO alertas (id_leitura, tipo, severidade, mensagem, data_hora) VALUES (?, ?, ?, ?, ?)");

        $alertas_gerados = 0;

        for ($i = 0; $i < $quantidade; $i++) {
            // Recua o tempo 1 minuto a cada iteração
            $data_hora = date('Y-m-d H:i:s', time() - ($i * 60));

            // Faixas de sorteio com base no tipo
            if ($gerar_anomalias && $i % 3 === 0) {
                // Sorteia valor acima do limite (Atenção ou Crítico)
                $valor = $limite_base + (mt_rand(1, 50) / 10);
            } else {
                // Sorteia valor normal
                $valor = ($limite_base * 0.5) + (mt_rand(1, intval($limite_base * 0.4 * 10)) / 10);
            }

            // Grava a leitura
            $stmt_leitura->bind_param("isdss", $fk_id_trem, $tipo_sensor, $valor, $unidade, $data_hora);
            $stmt_leitura->execute();
            $id_leitura = $conexao->insert_id;

            // Classificação automática da regra
            $severidade = classificar_leitura($tipo_sensor, $valor);

            // Grava alerta se não for normal
            if ($severidade !== 'normal') {
                $msg_alerta = "Alerta de " . ucfirst($tipo_sensor) . ": valor registrado " . number_format($valor, 2) . " " . $unidade . " ultrapassou o limite de " . $limite_base . " " . $unidade;
                $stmt_alerta->bind_param("issss", $id_leitura, $tipo_sensor, $severidade, $msg_alerta, $data_hora);
                $stmt_alerta->execute();
                $alertas_gerados++;
            }
        }

        $stmt_leitura->close();
        $stmt_alerta->close();

        $mensagem = "{$quantidade} leituras geradas com sucesso! ({$alertas_gerados} alertas de risco criados)";
        $tipo_msg = "sucesso";
    }
}

// Lista de Trens
$trens = $conexao->query("SELECT id_trem, prefixo_trem, modelo_trem FROM trens ORDER BY prefixo_trem ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Simulador de Sensores - GTT</title>
    <style>
        body { font-family: Arial, sans-serif; background: #222; color: #fff; padding: 30px; }
        .box { background: #cca424; color: #000; padding: 25px; border-radius: 12px; max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; }
        .form-group select, .form-group input { padding: 10px; border-radius: 6px; border: 1px solid #888; }
        .btn { background: #000; color: #cca424; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .sucesso { background: #2e7d32; color: #fff; }
        .erro { background: #c62828; color: #fff; }
        a { color: #dfd29d; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <p><a href="admin/controle.php">&larr; Voltar ao Painel</a></p>
        <h2>Simulador de Leituras de Sensores</h2>

        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php echo $tipo_msg; ?>"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form method="POST" action="simulador.php">
            <div class="form-group">
                <label>Selecione o Trem *</label>
                <select name="fk_id_trem" required>
                    <option value="">-- Selecione --</option>
                    <?php while ($t = $trens->fetch_assoc()): ?>
                        <option value="<?php echo $t['id_trem']; ?>">
                            <?php echo htmlspecialchars($t['prefixo_trem'] . ' - ' . $t['modelo_trem']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tipo de Sensor *</label>
                <select name="tipo_sensor" required>
                    <option value="velocidade">Velocidade (km/h)</option>
                    <option value="temperatura">Temperatura Motor (°C)</option>
                    <option value="consumo">Consumo (L/h)</option>
                    <option value="vibracao">Vibração (mm/s)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Quantidade de Leituras a Gerar *</label>
                <input type="number" name="quantidade" min="1" max="500" value="20" required>
            </div>

            <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px;">
                <input type="checkbox" id="anomalias" name="gerar_anomalias" value="1" checked>
                <label for="anomalias" style="margin: 0;">Gerar anomalias (valores acima do limite para testar alertas)</label>
            </div>

            <button type="submit" name="gerar" class="btn">Gerar Leituras</button>
        </form>
    </div>
</body>
</html>