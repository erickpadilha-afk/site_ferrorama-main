<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!empty($login) && !empty($senha)) {
        $stmt = $conexao->prepare("SELECT id, nome, login, senha, papel, COALESCE(ativo, 1) AS ativo FROM usuario WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($usuario = $resultado->fetch_assoc()) {
            if ((int)$usuario['ativo'] === 0) {
                $erro = "Sua conta está desativada. Entre em contato com o administrador.";
            } elseif (password_verify($senha, $usuario['senha'])) {
                $_SESSION['id_usuario'] = $usuario['id'];
                $_SESSION['nome']       = $usuario['nome'];
                $_SESSION['login']      = $usuario['login'];
                $_SESSION['papel']      = $usuario['papel'];

                if ($usuario['papel'] === 'administrador') {
                    header("Location: ../admin/admin.php");
                } elseif ($usuario['papel'] === 'maquinista') {
                    header("Location: ../relatorio/gera_relatorio.php");
                } else {
                    header("Location: ../dashboard/dashboard.php");
                }
                exit;
            } else {
                $erro = "Utilizador ou senha incorretos!";
            }
        } else {
            $erro = "Utilizador ou senha incorretos!";
        }
    } else {
        $erro = "Preencha todos os campos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GTT</title>
    <!-- O parâmetro ?v=3 força a atualização limpa do CSS sem cache -->
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>
    <div class="logo-container">
        <div class="circle"></div>
        <img src="../images/GTT-logo.png" alt="Logo GTT" class="logo">
    </div>
    
    <h3 class="slogan" style="color: var(--color-gold-base);">Onde a excelência se encontra com os trilhos</h3>
    
    <div class="login-box">
        <?php if (!empty($erro)): ?>
            <p style="color: #ff4d4d; text-align: center; margin-bottom: 15px; font-weight: bold;">
                <?php echo htmlspecialchars($erro); ?>
            </p>
        <?php endif; ?>

        <form id="loginform" class="login-form" method="POST" autocomplete="off">
            <div class="input-group">
                <input type="email" id="email" name="login" placeholder="E-mail" required autocomplete="off" onfocus="this.placeholder=''" onblur="this.placeholder='E-mail'">
            </div>
            <div class="input-group">
                <input type="password" id="senha" name="senha" placeholder="Senha" required autocomplete="new-password" onfocus="this.placeholder=''" onblur="this.placeholder='Senha'">
            </div>
            
            <div class="botoes-container">
                <a id="password-button" href="../nova_senha/NovaSenha.php">Esqueci a senha</a>
                <button type="submit" id="login-button">Entrar</button>
            </div>
        </form>
    </div>

    <div class="cadastro-container">
        <p class="texto-claro">Não tem um login?</p>
        <a id="register-button" href="cadastro.php">Cadastrar-se</a>
    </div>

    <script src="script.js"></script>
</body>
</html>