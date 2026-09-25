<?php
session_start();

// Caminho para conectar ao banco a partir da pasta 'nova senha'
require_once __DIR__ . '/../config/conexao.php';

$mensagem = "";
$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login       = trim($_POST['login'] ?? '');
    $nova_senha  = trim($_POST['nova_senha'] ?? '');
    $conf_senha  = trim($_POST['conf_senha'] ?? '');

    // 1. Validação de campos vazios
    if (!empty($login) && !empty($nova_senha) && !empty($conf_senha)) {
        
        // 2. Verifica se a nova senha e a confirmação coincidem
        if ($nova_senha !== $conf_senha) {
            $erro = "As senhas digitadas não coincidem!";
        } else {
            // 3. Verifica se o usuário/login existe no banco de dados
            $stmt_check = $conexao->prepare("SELECT id FROM usuario WHERE login = ?");
            $stmt_check->bind_param("s", $login);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();

            if ($res_check->num_rows === 0) {
                $erro = "Usuário/Login não encontrado!";
            } else {
                // 4. Gera o hash seguro da nova senha e atualiza no MySQL
                $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

                $stmt_update = $conexao->prepare("UPDATE usuario SET senha = ? WHERE login = ?");
                $stmt_update->bind_param("ss", $nova_senha_hash, $login);

                if ($stmt_update->execute()) {
                    $mensagem = "Senha alterada com sucesso! Você já pode fazer login.";
                } else {
                    $erro = "Erro ao atualizar a senha no banco de dados.";
                }
            }
        }
    } else {
        $erro = "Por favor, preencha todos os campos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - GTT</title>
    <link rel="stylesheet" href="NovaSenha.css">
</head>
<body>
    <div class="logo-container">
        <div class="circle"></div>
        <img src="../images/GTT-logo.png" alt="Logo GTT" class="logo">
    </div>
    <h3 class="slogan" style="color: gold;">Recuperação de Senha</h3>

    <div class="login-box">
        <?php if (!empty($mensagem)): ?>
            <p style="color: #4CAF50; text-align: center; font-weight: bold; margin-bottom: 15px;">
                <?php echo htmlspecialchars($mensagem); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <p style="color: #ff4d4d; text-align: center; font-weight: bold; margin-bottom: 15px;">
                <?php echo htmlspecialchars($erro); ?>
            </p>
        <?php endif; ?>

        <form id="novaSenhaForm" class="login-form" method="POST" action="NovaSenha.php">
            <div class="input-group">
                <input type="text" id="login" name="login" placeholder="Login / Usuário" required>
            </div>

            <div class="input-group">
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Nova Senha" required>
            </div>

            <div class="input-group">
                <input type="password" id="conf_senha" name="conf_senha" placeholder="Confirmar Nova Senha" required>
            </div>

            <div class="botoes-container" style="display: flex; gap: 10px; justify-content: space-between;">
                <a href="../pagina-inicial/index.php" style="color: #ccc; text-decoration: none; align-self: center;">Voltar ao Login</a>
                <button type="submit" id="btn-alterar">Alterar Senha</button>
            </div>
        </form>
    </div>

    <script src="NovaSenha.js"></script>
</body>
</html>