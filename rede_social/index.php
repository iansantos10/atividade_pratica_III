<?php
session_start();

require_once "config/database.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");
    $senha = trim($_POST["senha"] ?? "");

    if (empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } else {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 1) {
                $usuario = $resultado->fetch_assoc();

                if (password_verify($senha, $usuario["senha"])) {
                    $_SESSION["usuario_id"] = $usuario["id"];
                    $_SESSION["nome"] = $usuario["nome"];
                    $_SESSION["usuario"] = $usuario["username"];
                    $_SESSION["email"] = $usuario["email"];
                    $_SESSION["foto"] = $usuario["foto"];

                    header("Location: feed.php");
                    exit;
                } else {
                    $erro = "E-mail ou senha inválidos.";
                }
            } else {
                $erro = "E-mail ou senha inválidos.";
            }

            $stmt->close();
        } else {
            $erro = "Erro ao preparar a consulta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - VibeX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/icon_vibex.png">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="glow glow-1"></div>
            <div class="glow glow-2"></div>

            <img src="img/icon_vibex.png" alt="Logo VibeX" class="logo">

            <div class="titulo">VibeX</div>
            <div class="subtitulo">Entre na sua conta e conecte-se ao seu feed</div>

            <?php if (!empty($erro)) : ?>
                <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="campo">
                    <label for="email">E-mail</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        placeholder="Digite seu e-mail"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    >
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>
                    <input 
                        type="password" 
                        name="senha" 
                        id="senha" 
                        placeholder="Digite sua senha"
                    >
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>

            <div class="cadastro-link">
                Não tem conta? <a href="cadastro.php">Cadastre-se</a>
            </div>

            <div class="footer">VibeX © 2026</div>
        </div>
    </div>

</body>
</html>