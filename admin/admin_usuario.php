<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Bloqueio de acesso para usuários não autenticados ou não administradores
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../pagina-inicial/index.php");
    exit;
}

if (isset($_SESSION['papel']) && $_SESSION['papel'] !== 'administrador') {
    header("Location: ../dashboard/dashboard.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$mensagem = "";
$tipo_mensagem = "";

// Processar atualização do próprio perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_salvar_perfil'])) {
    $nome  = trim($_POST['nome'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($nome) || empty($login)) {
        $mensagem = "Por favor, preencha os campos obrigatórios (Nome e Login)!";
        $tipo_mensagem = "erro";
    } else {
        if (!empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conexao->prepare("UPDATE usuario SET nome = ?, login = ?, senha = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nome, $login, $senha_hash, $id_usuario);
        } else {
            $stmt = $conexao->prepare("UPDATE usuario SET nome = ?, login = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nome, $login, $id_usuario);
        }

        if ($stmt->execute()) {
            $_SESSION['nome'] = $nome;
            $_SESSION['login'] = $login;
            $mensagem = "Perfil atualizado com sucesso!";
            $tipo_mensagem = "sucesso";
        } else {
            $mensagem = "Erro ao atualizar perfil. O login informado já pode estar em uso.";
            $tipo_mensagem = "erro";
        }
    }
}

// Buscar dados do usuário logado diretamente com base no esquema da tabela
$stmt = $conexao->prepare("SELECT id, nome, login, papel FROM usuario WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$dados_usuario = $stmt->get_result()->fetch_assoc();

$nome_exibicao = $dados_usuario['nome'] ?? $_SESSION['nome'] ?? 'Administrador';
$primeiro_nome = explode(' ', trim($nome_exibicao))[0];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Administrador</title>
    <link rel="stylesheet" href="admin_usuario.css">
</head>
<body>

  <nav class="sticky-bar">
        <div class="nav-left">
            <div class="logo-container">
                <a href="admin.php">
                    <img src="../images/GTT-logo.png" alt="Logo" class="nav-logo" onerror="this.style.display='none'">
                </a>
            </div>
            <ul class="nav-links">
                <li class="nav-item"><a href="admin.php">Análise</a></li>
                <li class="nav-item tab-controle"><a href="controle.php">Controle</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <a href="admin_usuario.php" class="user-btn active-user">
                <span><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <svg class="user-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </a>
        </div>
    </nav>

    <main class="main-content">

        <h1 class="page-title">Perfil do Administrador</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert-msg <?php echo $tipo_mensagem; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            
            <!-- Cartão Visual do Perfil -->
            <section class="profile-card">
                <div class="avatar-circle">
                    <img src="../images/usua.png" alt="Foto do Usuário" onerror="this.src='https://via.placeholder.com/120'">
                </div>
                <h2><?php echo htmlspecialchars($dados_usuario['nome'] ?? 'Administrador'); ?></h2>
                <span class="badge-papel"><?php echo ucfirst($dados_usuario['papel'] ?? 'Administrador'); ?></span>
                
                <div class="info-list">
                    <p><strong>ID do Usuário:</strong> #<?php echo $dados_usuario['id'] ?? $id_usuario; ?></p>
                    <p><strong>Login:</strong> <?php echo htmlspecialchars($dados_usuario['login'] ?? '-'); ?></p>
                    <p><strong>Perfil de Acesso:</strong> <?php echo ucfirst($dados_usuario['papel'] ?? 'Administrador'); ?></p>
                </div>

                <a href="../pagina-inicial/index.php" class="btn-logout">Sair do Sistema</a>
            </section>

            <!-- Formulário para Editar Dados do Próprio Perfil -->
            <section class="form-section">
                <h2>Editar Meus Dados</h2>
                
                <form method="POST" action="admin_usuario.php" class="form-profile">
                    <div class="form-group">
                        <label>Nome Completo *</label>
                        <input type="text" name="nome" required value="<?php echo htmlspecialchars($dados_usuario['nome'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Login *</label>
                        <input type="text" name="login" required value="<?php echo htmlspecialchars($dados_usuario['login'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Nova Senha (deixe em branco para manter a atual)</label>
                        <input type="password" name="senha" placeholder="••••••••">
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="acao_salvar_perfil" class="btn-submit">Salvar Alterações</button>
                    </div>
                </form>
            </section>

        </div>
    </main>

</body>
</html>