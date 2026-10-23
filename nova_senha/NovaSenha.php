<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

$mensagem = "";
$tipo_mensagem = "";

// Se o usuário acessar a página diretamente (GET) sem enviar um formulário e sem clicar em "reset",
// limpamos o estado para forçar a inicialização na Etapa 1
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['reset']) && !isset($_GET['manter_etapa'])) {
    unset($_SESSION['recuperacao_etapa'], $_SESSION['recuperacao_email'], $_SESSION['recuperacao_codigo']);
}

// Botão / Ação de reset explícita
if (isset($_GET['reset'])) {
    unset($_SESSION['recuperacao_etapa'], $_SESSION['recuperacao_email'], $_SESSION['recuperacao_codigo']);
    header("Location: NovaSenha.php");
    exit;
}

$etapa = $_SESSION['recuperacao_etapa'] ?? 1;

// ETAPA 1: SOLICITAR CÓDIGO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_solicitar_codigo'])) {
    $email = trim($_POST['email'] ?? '');

    if (!empty($email) && isset($conexao) && $conexao) {
        $stmt = $conexao->prepare("SELECT id FROM usuario WHERE login = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $codigo = sprintf("%06d", mt_rand(100000, 999999));
            $_SESSION['recuperacao_email']  = $email;
            $_SESSION['recuperacao_codigo'] = $codigo;
            $_SESSION['recuperacao_etapa']  = 2;
            $etapa = 2;

            $mensagem = "Código gerado com sucesso: <strong>{$codigo}</strong>";
            $tipo_mensagem = "sucesso";
        } else {
            $mensagem = "O e-mail informado não está cadastrado!";
            $tipo_mensagem = "erro";
        }
    }
}

// ETAPA 2: REDEFINIR SENHA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_redefinir_senha'])) {
    $codigo_digitado = trim($_POST['codigo'] ?? '');
    $nova_senha      = $_POST['nova_senha'] ?? '';
    $confirma_senha  = $_POST['confirma_senha'] ?? '';
    
    $email_sessao  = $_SESSION['recuperacao_email'] ?? '';
    $codigo_sessao = $_SESSION['recuperacao_codigo'] ?? '';

    if ($codigo_digitado !== $codigo_sessao) {
        $mensagem = "Código de verificação incorreto!";
        $tipo_mensagem = "erro";
    } elseif ($nova_senha !== $confirma_senha) {
        $mensagem = "As senhas não coincidem!";
        $tipo_mensagem = "erro";
    } else {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt_up = $conexao->prepare("UPDATE usuario SET senha = ? WHERE login = ?");
        $stmt_up->bind_param("ss", $senha_hash, $email_sessao);

        if ($stmt_up->execute()) {
            unset($_SESSION['recuperacao_etapa'], $_SESSION['recuperacao_email'], $_SESSION['recuperacao_codigo']);
            header("Location: ../pagina-inicial/index.php?msg=senha_alterada");
            exit;
        } else {
            $mensagem = "Erro ao atualizar a senha.";
            $tipo_mensagem = "erro";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - GTT</title>
    <!-- Versão do CSS alterada para v=3 para evitar cache -->
    <link rel="stylesheet" href="NovaSenha.css?v=3">
</head>
<body>

    <div class="logo-container">
        <div class="circle"></div>
        <img src="../images/GTT-logo.png" alt="Logo GTT" class="logo" onerror="this.style.display='none'">
    </div>

    <h3 class="slogan">Recuperação de Acesso GTT</h3>

    <div class="login-box">
        
        <?php if (!empty($mensagem)): ?>
            <div class="msg-box msg-<?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <?php if ($etapa === 1): ?>
            <!-- ETAPA 1 -->
            <form id="formEtapa1" method="POST" action="NovaSenha.php" class="login-form" autocomplete="off">
                <div class="input-group">
                    <input type="email" id="email" name="email" placeholder="Digite seu E-mail" required autocomplete="off" onfocus="this.placeholder=''" onblur="this.placeholder='Digite seu E-mail'">
                </div>

                <div class="botoes-container">
                    <button type="submit" name="acao_solicitar_codigo" class="btn-submit">Enviar Código</button>
                    <a href="../pagina-inicial/index.php" class="btn-link">&larr; Voltar ao Login</a>
                </div>
            </form>

        <?php else: ?>
            <!-- ETAPA 2 -->
            <form id="formEtapa2" method="POST" action="NovaSenha.php" class="login-form" autocomplete="off">
                <p class="txt-email-destino">
                    Para: <strong><?php echo htmlspecialchars($_SESSION['recuperacao_email'] ?? ''); ?></strong>
                </p> <br>

                <div class="input-group">
                    <input type="text" id="codigo" name="codigo" maxlength="6" placeholder="Código (6 dígitos)" required autocomplete="off" onfocus="this.placeholder=''" onblur="this.placeholder='Código (6 dígitos)'">
                </div>
                <div class="input-group">
                    <input type="password" id="nova_senha" name="nova_senha" placeholder="Nova Senha" required autocomplete="new-password" onfocus="this.placeholder=''" onblur="this.placeholder='Nova Senha'">
                </div>
                <div class="input-group">
                    <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Confirme a Senha" required autocomplete="new-password" onfocus="this.placeholder=''" onblur="this.placeholder='Confirme a Senha'">
                </div>

                <div class="botoes-container">
                    <button type="submit" name="acao_redefinir_senha" class="btn-submit">Alterar Senha</button>
                    <a href="NovaSenha.php?reset=1" class="btn-link">Trocar E-mail</a>
                </div>
            </form>
        <?php endif; ?>

    </div>

    <script src="NovaSenha.js"></script>
</body>
</html>