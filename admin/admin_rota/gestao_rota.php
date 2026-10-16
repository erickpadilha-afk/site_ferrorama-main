<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';

if (!isset($_SESSION['id_usuario']) || ($_SESSION['papel'] ?? '') !== 'administrador') {
    header("Location: ../../pagina-inicial/index.php");
    exit;
}

$mensagem = "";
$tipo_mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_salvar'])) {
    $id_rota      = !empty($_POST['id_rota']) ? intval($_POST['id_rota']) : null;
    $id_trem      = intval($_POST['id_trem'] ?? 0);
    $origem       = trim($_POST['origem'] ?? '');
    $destino      = trim($_POST['destino'] ?? '');
    $data_viagem  = trim($_POST['data_viagem'] ?? '');
    $hora_partida = trim($_POST['hora_partida'] ?? '');
    $hora_chegada = !empty($_POST['hora_chegada']) ? trim($_POST['hora_chegada']) : null;
    $status       = trim($_POST['status'] ?? 'programada');

    if (empty($id_trem) || empty($origem) || empty($destino) || empty($data_viagem) || empty($hora_partida)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios!";
        $tipo_mensagem = "erro";
    } else {
        if ($id_rota) {
            $stmt = $conexao->prepare("UPDATE rotas SET id_trem = ?, origem = ?, destino = ?, data_viagem = ?, hora_partida = ?, hora_chegada = ?, status = ? WHERE id_rota = ?");
            $stmt->bind_param("issssssi", $id_trem, $origem, $destino, $data_viagem, $hora_partida, $hora_chegada, $status, $id_rota);
            if ($stmt->execute()) {
                header("Location: gestao_rota.php?msg=atualizado");
                exit;
            } else {
                $mensagem = "Erro ao atualizar rota.";
                $tipo_mensagem = "erro";
            }
        } else {
            $stmt = $conexao->prepare("INSERT INTO rotas (id_trem, origem, destino, data_viagem, hora_partida, hora_chegada, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $id_trem, $origem, $destino, $data_viagem, $hora_partida, $hora_chegada, $status);
            if ($stmt->execute()) {
                header("Location: gestao_rota.php?msg=criado");
                exit;
            } else {
                $mensagem = "Erro ao cadastrar rota.";
                $tipo_mensagem = "erro";
            }
        }
    }
}

if (isset($_GET['excluir'])) {
    $id_excluir = intval($_GET['excluir']);
    $stmt = $conexao->prepare("DELETE FROM rotas WHERE id_rota = ?");
    $stmt->bind_param("i", $id_excluir);
    if ($stmt->execute()) {
        header("Location: gestao_rota.php?msg=excluido");
        exit;
    } else {
        $mensagem = "Erro ao excluir rota.";
        $tipo_mensagem = "erro";
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'criado') { $mensagem = "Rota cadastrada com sucesso!"; $tipo_mensagem = "sucesso"; }
    elseif ($_GET['msg'] === 'atualizado') { $mensagem = "Rota atualizada com sucesso!"; $tipo_mensagem = "sucesso"; }
    elseif ($_GET['msg'] === 'excluido') { $mensagem = "Rota excluída com sucesso!"; $tipo_mensagem = "sucesso"; }
}

$trens_select = $conexao->query("SELECT id_trem, prefixo_trem, modelo_trem FROM trens ORDER BY prefixo_trem ASC");

$f_status      = trim($_GET['status'] ?? '');
$f_id_trem     = intval($_GET['id_trem'] ?? 0);
$f_data_inicio = trim($_GET['data_inicio'] ?? '');
$f_data_fim    = trim($_GET['data_fim'] ?? '');
$f_busca       = trim($_GET['busca'] ?? '');

$condicoes = [];
$valores   = [];
$tipos     = '';

if ($f_status !== '') {
    $condicoes[] = 'r.status = ?';
    $valores[]   = $f_status;
    $tipos      .= 's';
}
if ($f_id_trem > 0) {
    $condicoes[] = 'r.id_trem = ?';
    $valores[]   = $f_id_trem;
    $tipos      .= 'i';
}
if ($f_data_inicio !== '' && $f_data_fim !== '') {
    $condicoes[] = 'r.data_viagem BETWEEN ? AND ?';
    $valores[]   = $f_data_inicio;
    $valores[]   = $f_data_fim;
    $tipos      .= 'ss';
}
if ($f_busca !== '') {
    $condicoes[] = '(r.origem LIKE ? OR r.destino LIKE ?)';
    $termo       = "%{$f_busca}%";
    $valores[]   = $termo;
    $valores[]   = $termo;
    $tipos      .= 'ss';
}

$sql = "SELECT r.*, t.prefixo_trem, t.modelo_trem 
        FROM rotas r 
        INNER JOIN trens t ON r.id_trem = t.id_trem";

if (!empty($condicoes)) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}
$sql .= " ORDER BY r.data_viagem DESC, r.hora_partida ASC";

if (!empty($valores)) {
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $rotas = $stmt->get_result();
} else {
    $rotas = $conexao->query($sql);
}

$rota_edicao = null;
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $stmt_e = $conexao->prepare("SELECT * FROM rotas WHERE id_rota = ?");
    $stmt_e->bind_param("i", $id_editar);
    $stmt_e->execute();
    $rota_edicao = $stmt_e->get_result()->fetch_assoc();
}

$primeiro_nome = explode(' ', trim($_SESSION['nome'] ?? 'Admin'))[0];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Rotas - GTT</title>
    <link rel="stylesheet" href="gestao_rota.css">
</head>
<body>

    <nav class="sticky-bar">
        <div class="nav-left">
            <div class="logo-container">
                <a href="../admin.php"><img src="../../images/GTT-logo.png" alt="Logo" class="nav-logo"></a>
            </div>
            <ul class="nav-links">
                <li class="nav-item"><a href="../admin.php">Análise</a></li>
                <li class="nav-item active"><a href="../controle.php">Controle</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <a href="../../dashboard/Barra/usuario/usuario.php" class="user-btn">
                <span><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <svg class="user-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <a href="../controle.php" class="btn-voltar">&larr; Voltar ao Painel</a>
        <h1 class="page-title">Gestão de Rotas e Viagens</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert-msg <?php echo $tipo_mensagem; ?>"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <section class="form-section">
            <h2><?php echo $rota_edicao ? 'Editar Rota' : 'Cadastrar Nova Rota'; ?></h2>
            <form method="POST" action="gestao_rota.php" class="form-grid">
                <input type="hidden" name="id_rota" value="<?php echo $rota_edicao['id_rota'] ?? ''; ?>">

                <div class="form-group">
                    <label>Trem *</label>
                    <select name="id_trem" required>
                        <option value="">-- Selecione o Trem --</option>
                        <?php if ($trens_select && $trens_select->num_rows > 0): ?>
                            <?php $trens_select->data_seek(0); while($t = $trens_select->fetch_assoc()): ?>
                                <option value="<?php echo $t['id_trem']; ?>" <?php echo (($rota_edicao['id_trem'] ?? '') == $t['id_trem']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['prefixo_trem'] . ' - ' . $t['modelo_trem']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Origem *</label>
                    <input type="text" name="origem" placeholder="Ex: Joinville" required value="<?php echo htmlspecialchars($rota_edicao['origem'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Destino *</label>
                    <input type="text" name="destino" placeholder="Ex: Curitiba" required value="<?php echo htmlspecialchars($rota_edicao['destino'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Data da Viagem *</label>
                    <input type="date" name="data_viagem" required value="<?php echo $rota_edicao['data_viagem'] ?? date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Hora Partida *</label>
                    <input type="time" name="hora_partida" required value="<?php echo $rota_edicao['hora_partida'] ?? '08:00'; ?>">
                </div>

                <div class="form-group">
                    <label>Hora Chegada (Prevista/Efetiva)</label>
                    <input type="time" name="hora_chegada" value="<?php echo $rota_edicao['hora_chegada'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="programada" <?php echo (($rota_edicao['status'] ?? '') === 'programada') ? 'selected' : ''; ?>>Programada</option>
                        <option value="em andamento" <?php echo (($rota_edicao['status'] ?? '') === 'em andamento') ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="concluida" <?php echo (($rota_edicao['status'] ?? '') === 'concluida') ? 'selected' : ''; ?>>Concluída</option>
                        <option value="cancelada" <?php echo (($rota_edicao['status'] ?? '') === 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="acao_salvar" class="btn-submit"><?php echo $rota_edicao ? 'Atualizar Rota' : 'Salvar Rota'; ?></button>
                    <?php if ($rota_edicao): ?><a href="gestao_rota.php" class="btn-cancelar">Cancelar</a><?php endif; ?>
                </div>
            </form>
        </section>

        <section class="search-section">
            <form method="GET" action="gestao_rota.php" class="filter-form">
                <input type="text" name="busca" placeholder="Buscar Origem/Destino..." value="<?php echo htmlspecialchars($f_busca); ?>">
                
                <select name="status">
                    <option value="">-- Todos os Status --</option>
                    <option value="programada" <?php echo ($f_status === 'programada') ? 'selected' : ''; ?>>Programada</option>
                    <option value="em andamento" <?php echo ($f_status === 'em andamento') ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="concluida" <?php echo ($f_status === 'concluida') ? 'selected' : ''; ?>>Concluída</option>
                    <option value="cancelada" <?php echo ($f_status === 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                </select>

                <select name="id_trem">
                    <option value="0">-- Todos os Trens --</option>
                    <?php if ($trens_select && $trens_select->num_rows > 0): ?>
                        <?php $trens_select->data_seek(0); while($t = $trens_select->fetch_assoc()): ?>
                            <option value="<?php echo $t['id_trem']; ?>" <?php echo ($f_id_trem == $t['id_trem']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['prefixo_trem']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>

                <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($f_data_inicio); ?>">
                <input type="date" name="data_fim" value="<?php echo htmlspecialchars($f_data_fim); ?>">

                <button type="submit" class="btn-search">Filtrar</button>
                <a href="gestao_rota.php" class="btn-clear">Limpar</a>
            </form>
        </section>

        <section class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trem</th>
                        <th>Origem &rarr; Destino</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rotas && $rotas->num_rows > 0): ?>
                        <?php while ($row = $rotas->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id_rota']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['prefixo_trem']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['origem'] . ' &rarr; ' . $row['destino']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['data_viagem'])); ?></td>
                                <td><?php echo substr($row['hora_partida'], 0, 5) . ($row['hora_chegada'] ? ' / ' . substr($row['hora_chegada'], 0, 5) : ''); ?></td>
                                <td><span class="badge status-<?php echo str_replace(' ', '-', $row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td class="actions-cell">
                                    <a href="gestao_rota.php?editar=<?php echo $row['id_rota']; ?>" class="btn-edit">Editar</a>
                                    <a href="gestao_rota.php?excluir=<?php echo $row['id_rota']; ?>" class="btn-delete" onclick="return confirm('Excluir esta rota?');">Excluir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-table">Nenhuma rota encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>