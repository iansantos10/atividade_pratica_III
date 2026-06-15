<?php
session_start();

require_once "config/database.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$erro = "";
$usuario_id = $_SESSION["usuario_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["postar"])) {
    $conteudo = trim($_POST["conteudo"] ?? "");

    if (empty($conteudo)) {
        $erro = "O campo do post não pode estar vazio.";
    } else {
        $sql = "INSERT INTO posts (usuario_id, conteudo) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $usuario_id, $conteudo);
        $stmt->execute();

        header("Location: feed.php");
        exit;
    }
}

$sqlUsuario = "SELECT nome, username, foto FROM usuarios WHERE id = ?";
$stmtUsuario = $conn->prepare($sqlUsuario);
$stmtUsuario->bind_param("i", $usuario_id);
$stmtUsuario->execute();
$usuarioLogado = $stmtUsuario->get_result()->fetch_assoc();

$_SESSION["nome"] = $usuarioLogado["nome"];
$_SESSION["usuario"] = $usuarioLogado["username"];
$_SESSION["foto"] = $usuarioLogado["foto"];

$sql = "
SELECT 
    posts.id,
    posts.conteudo,
    posts.created_at,
    usuarios.nome,
    usuarios.username,
    usuarios.foto,
    COUNT(curtidas.id) AS total_curtidas,
    EXISTS (
        SELECT 1 
        FROM curtidas 
        WHERE curtidas.post_id = posts.id 
        AND curtidas.usuario_id = ?
    ) AS curtiu
FROM posts
INNER JOIN usuarios ON posts.usuario_id = usuarios.id
LEFT JOIN curtidas ON posts.id = curtidas.post_id
GROUP BY posts.id, posts.conteudo, posts.created_at, usuarios.nome, usuarios.username, usuarios.foto
ORDER BY posts.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$posts = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Feed - VibeX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/icon_vibex.png">
    <link rel="stylesheet" href="css/feed.css?v=2">
</head>
<body>

<div class="feed-layout">

    <aside class="sidebar">
        <div class="logo-box">
            <img src="img/icon_vibex.png" alt="Logo VibeX" class="logo">
            <h1>VibeX</h1>
            <p>Compartilhe suas ideias</p>
        </div>

        <nav class="menu">
            <a href="feed.php" class="menu-item active">🏠 <span>Início</span></a>
            <a href="perfil.php" class="menu-item">👤 <span>Meu Perfil</span></a>
            <a href="usuarios.php" class="menu-item">🔎 <span>Buscar Usuários</span></a>
            <a href="logout.php" class="menu-item">↩ <span>Sair</span></a>
        </nav>
    </aside>

    <main class="main-content">

        <section class="profile-panel" id="perfil">
            <div class="profile-top">
                <div class="profile-info">
                    <img src="img/<?php echo htmlspecialchars($usuarioLogado["foto"]); ?>" alt="Foto do usuário" class="profile-pic">
                    <div>
                        <h2><?php echo htmlspecialchars($usuarioLogado["nome"]); ?></h2>
                        <p>@<?php echo htmlspecialchars($usuarioLogado["username"]); ?></p>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="perfil.php" class="btn-editar">Editar Perfil</a>
                </div>
            </div>
        </section>

        <section class="composer-card" id="postar">
            <h3>Nova postagem</h3>

            <?php if (!empty($erro)) : ?>
                <div class="erro-msg"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST" class="composer-form">
                <textarea name="conteudo" placeholder="Quais são as novidades?"></textarea>
                <button type="submit" name="postar">Postar</button>
            </form>
        </section>

        <section class="feed-posts">

            <?php if ($posts->num_rows == 0) : ?>
                <article class="post-card">
                    <div class="post-content">
                        Nenhum post encontrado. Faça sua primeira postagem!
                    </div>
                </article>
            <?php endif; ?>

            <?php while ($post = $posts->fetch_assoc()) : ?>
                <article class="post-card">
                    <div class="post-top">
                        <img src="img/<?php echo htmlspecialchars($post["foto"]); ?>" alt="Foto" class="post-pic">
                        <div>
                            <h4><?php echo htmlspecialchars($post["nome"]); ?></h4>
                            <span>@<?php echo htmlspecialchars($post["username"]); ?></span>
                        </div>
                    </div>

                    <div class="post-content">
                        <?php echo htmlspecialchars($post["conteudo"]); ?>
                    </div>

                    <div class="post-actions">
                        <button 
                            type="button" 
                            class="btn-curtir" 
                            data-post-id="<?php echo $post["id"]; ?>"
                        >
                            <?php echo $post["curtiu"] ? "💔 Descurtir" : "❤️ Curtir"; ?>
                        </button>

                        <span id="curtidas-<?php echo $post["id"]; ?>">
                            <?php echo $post["total_curtidas"]; ?> curtidas
                        </span>
                    </div>
                </article>
            <?php endwhile; ?>

        </section>

    </main>
</div>

<script>
document.querySelectorAll(".btn-curtir").forEach(botao => {
    botao.addEventListener("click", function () {
        const postId = this.getAttribute("data-post-id");
        const botaoAtual = this;

        fetch("curtir.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "post_id=" + postId
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                document.getElementById("curtidas-" + postId).innerText = data.total + " curtidas";
                botaoAtual.innerText = data.curtiu ? "💔 Descurtir" : "❤️ Curtir";
            }
        });
    });
});
</script>

</body>
</html>