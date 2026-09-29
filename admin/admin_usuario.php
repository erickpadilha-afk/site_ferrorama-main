<?php
session_start();

require_once __DIR__ . '/../../../config/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../../pagina-inicial/index.php");
    exit;
}

$texto_usuario = '';

if (isset($_SESSION['nome']) && !empty($_SESSION['nome'])) {
    $texto_usuario = $_SESSION['nome'];
} else {
    $id_usuario = $_SESSION['id_usuario'];
    $stmt = $conexao->prepare("SELECT nome FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($user = $res->fetch_assoc()) {
        $texto_usuario = $user['nome'];
    } else {
        $texto_usuario = $_SESSION['login'] ?? 'Usuário';
    }
}

if (strpos($texto_usuario, '@') !== false) {
    $texto_usuario = explode('@', $texto_usuario)[0];
}
$palavras = preg_split('/[\s._-]+/', trim($texto_usuario));
$primeiro_nome = !empty($palavras[0]) ? mb_convert_case($palavras[0], MB_CASE_TITLE, "UTF-8") : 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Train Track - Perfil</title>
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="usuaStyle.css">
</head>
<body>

    <nav class="sticky-bar">
        <div class="nav-left">
            <div class="logo-container">
                <a href="../../dashboard.php">
                    <img src="../../../images/GTT-logo.png" alt="Golden Train Track Logo">
                </a>
            </div>
            
            <ul class="nav-links">
                <li class="nav-item"><a href="../../dashboard.php">Inicio</a></li>
                <li class="nav-item"><a href="../trens/trens.php">Trens</a></li>
                <li class="nav-item tab-viajar"><a href="../viajar/viajar.php">Viajar</a></li>
            </ul>
        </div>

        <div class="nav-right">
            <a href="usuario.php" class="user-btn active-user">
                <span><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <svg class="user-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </a>
        </div>
    </nav>

    <main class="main-content">
        
        <section class="welcome-container">
            <div class="avatar-placeholder">
                <img src="../../../images/usua.png" alt="usuario foto">
            </div>
            <div class="welcome-text">
                <h2>Olá!</h2>
                <h1><?php echo htmlspecialchars($primeiro_nome); ?></h1>
            </div>
        </section>

        <section class="profile-card-container">
            <div class="profile-details-card">
                
                <button class="edit-profile-btn" title="Editar Perfil">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                    </svg>
                </button>

                <div class="info-group">
                    <p><strong>Email:</strong> lucasbmabreu@gmail.com</p>
                    <p><strong>Idade:</strong> 19</p>
                    <p class="spaced-info"><strong>CPF:</strong> 000.000.000-00</p>
                    <p><strong>Cidade/Município:</strong> São Paulo, SP</p>
                    <p><strong>Primeiro Acesso:</strong> 17/12/2025</p>
                    <p class="spaced-info"><strong>Status:</strong> <span class="status-active">Ativo</span></p>
                    <p><strong>Perfil de acesso:</strong> Cliente</p>
                    <p><strong>Telefone:</strong> (11) 99900-0000</p>
                </div>

                <a href="../../../pagina-inicial/index.php" class="logout-btn" id="logoutBtn">SAIR</a>
            </div>
        </section>

    </main>

    <script src="script.js"></script>
</body>
</html>