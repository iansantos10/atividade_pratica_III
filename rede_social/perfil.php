<?php
session_start();

require_once "config/database.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION["usuario_id"];
$erro = "";
$sucesso = "";

$sql = "
SELECT 
    u.*,
    (SELECT COUNT(*) FROM seguidores WHERE seguindo_id = u.id) AS total_seguidores,
    (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = u.id) AS total_seguindo,
    (SELECT COUNT(*) FROM posts WHERE usuario_id = u.id) AS total_posts
FROM usuarios u
WHERE u.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["atualizar_perfil"])) {
        $nome = trim($_POST["nome"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $data_nascimento = trim($_POST["data_nascimento"] ?? "");
        $genero = trim($_POST["genero"] ?? "");

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $username = ltrim($username, "@");

        if (empty($nome) || empty($username) || empty($email) || empty($data_nascimento) || empty($genero)) {
            $erro = "Preencha todos os campos do perfil.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Digite um e-mail válido.";
        } elseif (!in_array($genero, ["Feminino", "Masculino", "Outro"])) {
            $erro = "Selecione um gênero válido.";
        } else {
            $sqlVerifica = "SELECT id FROM usuarios WHERE (email = ? OR username = ?) AND id != ?";
            $stmtVerifica = $conn->prepare($sqlVerifica);
            $stmtVerifica->bind_param("ssi", $email, $username, $usuario_id);
            $stmtVerifica->execute();
            $resultado = $stmtVerifica->get_result();

            if ($resultado->num_rows > 0) {
                $erro = "E-mail ou nome de usuário já está em uso.";
            } else {
                $sqlUpdate = "UPDATE usuarios SET nome = ?, username = ?, email = ?, data_nascimento = ?, genero = ? WHERE id = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bind_param("sssssi", $nome, $username, $email, $data_nascimento, $genero, $usuario_id);

                if ($stmtUpdate->execute()) {
                    $sucesso = "Perfil atualizado com sucesso!";
                    $_SESSION["nome"] = $nome;
                    $_SESSION["usuario"] = $username;
                } else {
                    $erro = "Erro ao atualizar perfil.";
                }
            }
        }
    }

    if (isset($_POST["alterar_senha"])) {
        $senha_atual = trim($_POST["senha_atual"] ?? "");
        $nova_senha = trim($_POST["nova_senha"] ?? "");
        $confirmar_senha = trim($_POST["confirmar_senha"] ?? "");

        if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
            $erro = "Preencha todos os campos de senha.";
        } elseif (!password_verify($senha_atual, $usuario["senha"])) {
            $erro = "Senha atual incorreta.";
        } elseif (
            strlen($nova_senha) < 6 ||
            !preg_match('/[A-Z]/', $nova_senha) ||
            !preg_match('/[0-9]/', $nova_senha)
        ) {
            $erro = "A nova senha deve ter pelo menos 6 caracteres, 1 letra maiúscula e 1 número.";
        } elseif ($nova_senha !== $confirmar_senha) {
            $erro = "A confirmação da nova senha não coincide.";
        } else {
            $senhaCriptografada = password_hash($nova_senha, PASSWORD_DEFAULT);

            $sqlSenha = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmtSenha = $conn->prepare($sqlSenha);
            $stmtSenha->bind_param("si", $senhaCriptografada, $usuario_id);

            if ($stmtSenha->execute()) {
                $sucesso = "Senha alterada com sucesso!";
            } else {
                $erro = "Erro ao alterar senha.";
            }
        }
    }

    if (isset($_POST["alterar_foto"])) {
        if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
            $nomeArquivo = $_FILES["foto"]["name"];
            $tmp = $_FILES["foto"]["tmp_name"];
            $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));

            $extensoesPermitidas = ["jpg", "jpeg", "png"];

            if (!in_array($extensao, $extensoesPermitidas)) {
                $erro = "A foto deve estar no formato JPG, JPEG ou PNG.";
            } else {
                $novoNome = "usuario_" . $usuario_id . "_" . time() . "." . $extensao;
                $destino = "img/" . $novoNome;

                if (move_uploaded_file($tmp, $destino)) {
                    $sqlFoto = "UPDATE usuarios SET foto = ? WHERE id = ?";
                    $stmtFoto = $conn->prepare($sqlFoto);
                    $stmtFoto->bind_param("si", $novoNome, $usuario_id);

                    if ($stmtFoto->execute()) {
                        $_SESSION["foto"] = $novoNome;
                        $sucesso = "Foto alterada com sucesso!";
                    } else {
                        $erro = "Erro ao salvar foto no banco.";
                    }
                } else {
                    $erro = "Erro ao enviar a foto.";
                }
            }
        } else {
            $erro = "Selecione uma foto para enviar.";
        }
    }

    header("Location: perfil.php");
    exit;
}

$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - VibeX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/icon_vibex.png">
    <link rel="stylesheet" href="css/perfil.css">
</head>
<body>

<div class="perfil-layout">

    <aside class="sidebar">
        <div class="logo-box">
            <img src="img/icon_vibex.png" alt="Logo VibeX" class="logo">
            <h1>VibeX</h1>
            <p>Central do usuário</p>
        </div>

        <nav class="menu">
            <a href="feed.php" class="menu-item">🏠 <span>Início</span></a>
            <a href="perfil.php" class="menu-item active">👤 <span>Meu Perfil</span></a>
            <a href="usuarios.php" class="menu-item">🔎 <span>Buscar Usuários</span></a>
            <a href="logout.php" class="menu-item">↩ <span>Sair</span></a>
        </nav>
    </aside>

    <main class="perfil-main">

        <?php if (!empty($erro)) : ?>
            <div class="alerta erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)) : ?>
            <div class="alerta sucesso"><?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>

        <section class="perfil-header">
            <img src="img/<?php echo htmlspecialchars($usuario["foto"] ?: "padrao.png"); ?>" alt="Foto do usuário" class="perfil-foto">

            <div>
                <h2><?php echo htmlspecialchars($usuario["nome"]); ?></h2>
                <p>@<?php echo htmlspecialchars($usuario["username"]); ?></p>
            </div>
        </section>

        <section class="cards-resumo">
            <div class="card-numero">
                <strong><?php echo $usuario["total_seguidores"]; ?></strong>
                <span>Seguidores</span>
            </div>

            <div class="card-numero">
                <strong><?php echo $usuario["total_seguindo"]; ?></strong>
                <span>Seguindo</span>
            </div>

            <div class="card-numero">
                <strong><?php echo $usuario["total_posts"]; ?></strong>
                <span>Posts</span>
            </div>
        </section>

        <section class="perfil-grid">

            <div class="perfil-card">
                <h3>Informações da conta</h3>

                <div class="info-linha"><strong>Nome:</strong> <?php echo htmlspecialchars($usuario["nome"]); ?></div>
                <div class="info-linha"><strong>Username:</strong> @<?php echo htmlspecialchars($usuario["username"]); ?></div>
                <div class="info-linha"><strong>E-mail:</strong> <?php echo htmlspecialchars($usuario["email"]); ?></div>
                <div class="info-linha"><strong>Data de nascimento:</strong> <?php echo htmlspecialchars($usuario["data_nascimento"]); ?></div>
                <div class="info-linha"><strong>Gênero:</strong> <?php echo htmlspecialchars($usuario["genero"]); ?></div>
            </div>

            <div class="perfil-card">
                <h3>Editar informações</h3>

                <form method="POST" class="form-perfil">
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario["nome"]); ?>" placeholder="Nome completo">

                    <input type="text" name="username" value="<?php echo htmlspecialchars($usuario["username"]); ?>" placeholder="Username">

                    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario["email"]); ?>" placeholder="E-mail">

                    <input type="date" name="data_nascimento" value="<?php echo htmlspecialchars($usuario["data_nascimento"]); ?>">

                    <select name="genero">
                        <option value="">Selecione o gênero</option>
                        <option value="Feminino" <?php echo $usuario["genero"] == "Feminino" ? "selected" : ""; ?>>Feminino</option>
                        <option value="Masculino" <?php echo $usuario["genero"] == "Masculino" ? "selected" : ""; ?>>Masculino</option>
                        <option value="Outro" <?php echo $usuario["genero"] == "Outro" ? "selected" : ""; ?>>Outro</option>
                    </select>

                    <button type="submit" name="atualizar_perfil">Salvar alterações</button>
                </form>
            </div>

            <div class="perfil-card">
                <h3>Alterar foto</h3>

                <form method="POST" enctype="multipart/form-data" class="form-perfil">
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png">
                    <button type="submit" name="alterar_foto">Enviar nova foto</button>
                </form>
            </div>

            <div class="perfil-card">
                <h3>Alterar senha</h3>

                <form method="POST" class="form-perfil">
                    <input type="password" name="senha_atual" placeholder="Senha atual">
                    <input type="password" name="nova_senha" placeholder="Nova senha">
                    <input type="password" name="confirmar_senha" placeholder="Confirmar nova senha">

                    <button type="submit" name="alterar_senha">Alterar senha</button>
                </form>
            </div>

        </section>

    </main>
</div>

</body>
</html>