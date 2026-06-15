<?php
session_start();

require_once "config/database.php";

$erro = "";

$nome = "";
$usuario = "";
$email = "";
$data_nascimento = "";
$genero = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $usuario = trim($_POST["usuario"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = trim($_POST["senha"] ?? "");
    $confirmar_senha = trim($_POST["confirmar_senha"] ?? "");
    $data_nascimento = trim($_POST["data_nascimento"] ?? "");
    $genero = trim($_POST["genero"] ?? "");

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $usuario = ltrim($usuario, "@");

    if (
        empty($nome) || empty($usuario) || empty($email) ||
        empty($senha) || empty($confirmar_senha) ||
        empty($data_nascimento) || empty($genero)
    ) {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } elseif (
        strlen($senha) < 6 ||
        !preg_match('/[A-Z]/', $senha) ||
        !preg_match('/[0-9]/', $senha)
    ) {
        $erro = "A senha deve ter pelo menos 6 caracteres, 1 letra maiúscula e 1 número.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "A senha e a confirmação devem coincidir.";
    } elseif (!DateTime::createFromFormat('Y-m-d', $data_nascimento)) {
        $erro = "Digite uma data de nascimento válida.";
    } elseif (!in_array($genero, ["Feminino", "Masculino", "Outro"])) {
        $erro = "Selecione um gênero válido.";
    } else {
        $sqlVerifica = "SELECT id FROM usuarios WHERE email = ? OR username = ?";
        $stmtVerifica = $conn->prepare($sqlVerifica);
        $stmtVerifica->bind_param("ss", $email, $usuario);
        $stmtVerifica->execute();
        $resultado = $stmtVerifica->get_result();

        if ($resultado->num_rows > 0) {
            $erro = "E-mail ou nome de usuário já cadastrado.";
        } else {
            $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);
            $foto = "padrao.png";

            $sql = "INSERT INTO usuarios 
                    (nome, username, email, senha, data_nascimento, genero, foto) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssss",
                $nome,
                $usuario,
                $email,
                $senhaCriptografada,
                $data_nascimento,
                $genero,
                $foto
            );

            if ($stmt->execute()) {
                header("Location: index.php");
                exit;
            } else {
                $erro = "Erro ao cadastrar usuário.";
            }

            $stmt->close();
        }

        $stmtVerifica->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - VibeX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/png" href="img/icon_vibex.png">
    <link rel="stylesheet" href="css/cadastro.css">
</head>
<body>

    <div class="cadastro-container">
        <div class="cadastro-card">
            <img src="img/icon_vibex.png" alt="Logo VibeX" class="logo">

            <div class="titulo">VibeX</div>
            <div class="subtitulo">Crie sua conta</div>

            <?php if (!empty($erro)) : ?>
                <div class="mensagem erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="campo">
                    <label for="nome">Nome completo</label>
                    <input type="text" name="nome" id="nome" placeholder="Digite seu nome completo" value="<?php echo htmlspecialchars($nome); ?>">
                </div>

                <div class="campo">
                    <label for="usuario">Nome de usuário</label>
                    <input type="text" name="usuario" id="usuario" placeholder="Digite seu username" value="<?php echo htmlspecialchars($usuario); ?>">
                </div>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" placeholder="Digite seu e-mail" value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" placeholder="Digite sua senha">
                </div>

                <div class="campo">
                    <label for="confirmar_senha">Confirmação de senha</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" placeholder="Confirme sua senha">
                </div>

                <div class="campo">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" name="data_nascimento" id="data_nascimento" value="<?php echo htmlspecialchars($data_nascimento); ?>">
                </div>

                <div class="campo">
                    <label for="genero">Gênero</label>
                    <select name="genero" id="genero">
                        <option value="">Selecione</option>
                        <option value="Feminino" <?php echo ($genero == "Feminino") ? "selected" : ""; ?>>Feminino</option>
                        <option value="Masculino" <?php echo ($genero == "Masculino") ? "selected" : ""; ?>>Masculino</option>
                        <option value="Outro" <?php echo ($genero == "Outro") ? "selected" : ""; ?>>Outro</option>
                    </select>
                </div>

                <button type="submit" class="btn-cadastro">Cadastrar</button>
            </form>

            <div class="login-link">
                Já tem conta? <a href="index.php">Entrar</a>
            </div>

            <div class="footer">VibeX © 2026</div>
        </div>
    </div>

</body>
</html>