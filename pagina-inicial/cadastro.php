<?php
session_start();

require_once __DIR__ . '/../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}


$mensagem = "";
$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $papel = trim($_POST['papel'] ?? '');

    if (!empty($nome) && !empty($login) && !empty($senha) && !empty($papel)) {
        
        $stmt_check = $conexao->prepare("SELECT id FROM usuario WHERE login = ?");
        $stmt_check->bind_param("s", $login);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $erro = "Este login já está em uso por outro usuário!";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $conexao->prepare("INSERT INTO usuario (nome, login, senha, papel) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $login, $senha_hash, $papel);

            if ($stmt->execute()) {
                $mensagem = "Usuário cadastrado com sucesso!";
            } else {
                $erro = "Erro ao cadastrar usuário na base de dados.";
            }
        }
    } else {
        $erro = "Por favor, preencha todos os campos obrigatórios!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - GTT</title>
    <link rel="stylesheet" href="cadastro.css">
</head>
<body>
    <div class="logo-container">
        <div class="circle"></div>
        <img src="../images/GTT-logo.png" alt="Logo GTT" class="logo">
    </div>
    <h3 class="slogan" style="color: gold;">Cadastrar Novo Usuário</h3>
    
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

        <form id="loginform" class="login-form" method="POST">
            <div class="input-group">
                <input type="text" id="nome" name="nome" placeholder="Nome Completo" required>
            </div>
            
            <div class="input-group">
                <input type="text" id="login" name="login" placeholder="Login / Usuário" required>
            </div>
            
            <div class="input-group">
                <input type="password" id="senha" name="senha" placeholder="Senha" required>
            </div>
            
            <div class="input-group">
                <select name="papel" id="papel" required style="width: 100%; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                    <option value="" disabled selected>Selecione o Papel do Usuário</option>
                    <option value="administrador">Administrador</option>
                    <option value="maquinista">Maquinista</option>
                    <option value="gestor">Cliente</option>
                </select>
            </div>

            <div class="botoes-container" style="display: flex; gap: 10px; justify-content: space-between;">
                <a href="../pagina-inicial/index.php" style="color: #ccc; text-decoration: none; align-self: center;">Voltar</a>
                <button type="submit" id="btn-confirmar">Cadastrar</button>
            </div>
        </form>
    </div>

    <script src="cadastro.js"></script>
</body>
</html>