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
        $stmt = $conexao->prepare("SELECT id, login, senha, papel FROM usuario WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($usuario = $resultado->fetch_assoc()) {
            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['id_usuario'] = $usuario['id'];
                $_SESSION['login']      = $usuario['login'];
                $_SESSION['papel']      = $usuario['papel'];

                header("Location: ../dashboard/dashboard.php");
                exit;
            }
        }
        $erro = "Utilizador ou senha incorretos!";
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
    <link rel="stylesheet" href="style.css">
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

        <form id="loginform" class="login-form" method="POST">
            <div class="input-group">
                <input type="text" id="email" name="login" placeholder="Login ou E-mail" required>
            </div>
            <div class="input-group">
                <input type="password" id="senha" name="senha" placeholder="Senha" required>
            </div>
            
            <div class="botoes-container">
                <a id="password-button" href="../nova_senha/NovaSenha.php">Esqueci a senha</a>
                <button type="submit" id="login-button">Fazer login</button>
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