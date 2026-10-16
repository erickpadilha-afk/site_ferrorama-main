<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';

// Bloqueio de acesso para usuários não autenticados ou não administradores
if (!isset($_SESSION['id_usuario']) || ($_SESSION['papel'] ?? '') !== 'administrador') {
    header("Location: ../../pagina-inicial/index.php");
    exit;
}

$mensagem = "";
$tipo_mensagem = "";

// --- PROCESSAR EDIÇÃO DE USUÁRIO / CLIENTE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_salvar'])) {
    $id_usuario_form = !empty($_POST['id_usuario']) ? intval($_POST['id_usuario']) : null;
    $nome            = trim($_POST['nome'] ?? '');
    $login           = trim($_POST['login'] ?? '');
    $papel           = trim($_POST['papel'] ?? 'cliente');
    $senha           = $_POST['senha'] ?? '';

    if (empty($nome) || empty($login)) {
        $mensagem = "Por favor, preencha os campos obrigatórios (Nome e Login)!";
        $tipo_mensagem = "erro";
    } else {
        if ($id_usuario_form) {
            // Atualização de usuário existente
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conexao->prepare("UPDATE usuario SET nome = ?, login = ?, papel = ?, senha = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nome, $login, $papel, $senha_hash, $id_usuario_form);
            } else {
                $stmt = $conexao->prepare("UPDATE usuario SET nome = ?, login = ?, papel = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nome, $login, $papel, $id_usuario_form);
            }

            if ($stmt->execute()) {
                header("Location: gestao_cliente.php?msg=atualizado");
                exit;
            } else {
                $mensagem = "Erro ao atualizar usuário no banco de dados.";
                $tipo_mensagem = "erro";
            }
        } else {
            // Inserção de novo usuário
            if (empty($senha)) {
                $mensagem = "Informe uma senha para cadastrar o novo usuário!";
                $tipo_mensagem = "erro";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conexao->prepare("INSERT INTO usuario (nome, login, papel, senha) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nome, $login, $papel, $senha_hash);

                if ($stmt->execute()) {
                    header("Location: gestao_cliente.php?msg=criado");
                    exit;
                } else {
                    $mensagem = "Erro ao cadastrar usuário. O login pode já estar em uso.";
                    $tipo_mensagem = "erro";
                }
            }
        }
    }
}

// --- PROCESSAR EXCLUSÃO ---
if (isset($_GET['excluir'])) {
    $id_excluir = intval($_GET['excluir']);

    // Prevenir que o admin logado exclua a si mesmo
    if ($id_excluir === intval($_SESSION['id_usuario'])) {
        $mensagem = "Você não pode excluir a sua própria conta enquanto estiver conectado!";
        $tipo_mensagem = "erro";
    } else {
        $stmt = $conexao->prepare("DELETE FROM usuario WHERE id = ?");
        $stmt->bind_param("i", $id_excluir);

        if ($stmt->execute()) {
            header("Location: gestao_cliente.php?msg=excluido");
            exit;
        } else {
            $mensagem = "Erro ao excluir o usuário selecionado.";
            $tipo_mensagem = "erro";
        }
    }
}

// Feedback visual
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'criado') { $mensagem = "Usuário cadastrado com sucesso!"; $tipo_mensagem = "sucesso"; }
    elseif ($_GET['msg'] === 'atualizado') { $mensagem = "Usuário atualizado com sucesso!"; $tipo_mensagem = "sucesso"; }
    elseif ($_GET['msg'] === 'excluido') { $mensagem = "Usuário excluído com sucesso!"; $tipo_mensagem = "sucesso"; }
}

// --- CONSULTA E BUSCA DE USUÁRIOS ---
$termo_busca = trim($_GET['busca'] ?? '');

if (!empty($termo_busca)) {
    $stmt = $conexao->prepare("SELECT id, nome, login, papel FROM usuario WHERE nome LIKE ? OR login LIKE ? ORDER BY id DESC");
    $param_like = "%{$termo_busca}%";
    $stmt->bind_param("ss", $param_like, $param_like);
    $stmt->execute();
    $usuarios = $stmt->get_result();
} else {
    $usuarios = $conexao->query("SELECT id, nome, login, papel FROM usuario ORDER BY id DESC");
}

// Dados para edição
$usuario_edicao = null;
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $stmt_e = $conexao->prepare("SELECT id, nome, login, papel FROM usuario WHERE id = ?");
    $stmt_e->bind_param("i", $id_editar);
    $stmt_e->execute();
    $usuario_edicao = $stmt_e->get_result()->fetch_assoc();
}

$nome_completo = $_SESSION['nome'] ?? $_SESSION['login'] ?? 'Admin';
$primeiro_nome = explode(' ', trim($nome_completo))[0];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários e Clientes - GTT</title>
    <link rel="stylesheet" href="gestao_cliente.css">
</head>
<body>

    <nav class="sticky-bar">
        <div class="nav-left">
            <div class="logo-container">
                <a href="../admin.php">
                    <img src="../../images/GTT-logo.png" alt="Logo" class="nav-logo" onerror="this.style.display='none'">
                </a>
            </div>
            <ul class="nav-links">
                <li class="nav-item"><a href="../admin.php">Análise</a></li>
                <li class="nav-item active"><a href="../controle.php">Controle</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <a href="../admin_usuario.php" class="user-btn">
                <span><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <svg class="user-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <a href="../controle.php" class="btn-voltar">&larr; Voltar ao Painel</a>
        <h1 class="page-title">Gestão de Usuários e Clientes</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert-msg <?php echo $tipo_mensagem; ?>"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <!-- Formulário de Cadastro / Edição -->
        <section class="form-section">
            <h2><?php echo $usuario_edicao ? 'Editar Usuário' : 'Cadastrar Novo Usuário'; ?></h2>
            <form method="POST" action="gestao_cliente.php" class="form-grid">
                <input type="hidden" name="id_usuario" value="<?php echo $usuario_edicao['id'] ?? ''; ?>">

                <div class="form-group">
                    <label>Nome Completo *</label>
                    <input type="text" name="nome" placeholder="Ex: João da Silva" required value="<?php echo htmlspecialchars($usuario_edicao['nome'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Login *</label>
                    <input type="text" name="login" placeholder="Ex: joao.silva" required value="<?php echo htmlspecialchars($usuario_edicao['login'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Perfil de Acesso *</label>
                    <select name="papel" required>
                        <option value="cliente" <?php echo (($usuario_edicao['papel'] ?? '') === 'cliente') ? 'selected' : ''; ?>>Cliente / Passageiro</option>
                        <option value="administrador" <?php echo (($usuario_edicao['papel'] ?? '') === 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Senha <?php echo $usuario_edicao ? '(deixe em branco para manter)' : '*'; ?></label>
                    <input type="password" name="senha" placeholder="••••••••" <?php echo $usuario_edicao ? '' : 'required'; ?>>
                </div>

                <div class="form-actions">
                    <button type="submit" name="acao_salvar" class="btn-submit">
                        <?php echo $usuario_edicao ? 'Atualizar Usuário' : 'Salvar Usuário'; ?>
                    </button>
                    <?php if ($usuario_edicao): ?>
                        <a href="gestao_cliente.php" class="btn-cancelar">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Filtro de Busca -->
        <section class="search-section">
            <form method="GET" action="gestao_cliente.php" class="filter-form">
                <input type="text" name="busca" placeholder="Buscar por Nome ou Login..." value="<?php echo htmlspecialchars($termo_busca); ?>">
                <button type="submit" class="btn-search">Buscar</button>
                <?php if (!empty($termo_busca)): ?>
                    <a href="gestao_cliente.php" class="btn-clear">Limpar</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Tabela de Listagem de Usuários -->
        <section class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome Completo</th>
                        <th>Login</th>
                        <th>Perfil</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                        <?php while ($row = $usuarios->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['login']); ?></td>
                                <td>
                                    <span class="badge papel-<?php echo htmlspecialchars($row['papel']); ?>">
                                        <?php echo ucfirst($row['papel']); ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <a href="gestao_cliente.php?editar=<?php echo $row['id']; ?>" class="btn-edit">Editar</a>
                                    <?php if ($row['id'] !== intval($_SESSION['id_usuario'])): ?>
                                        <a href="gestao_cliente.php?excluir=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Deseja realmente excluir este usuário?');">Excluir</a>
                                    <?php else: ?>
                                        <span class="own-user-tag">(Sua Conta)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-table">Nenhum usuário encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>

</body>
</html>