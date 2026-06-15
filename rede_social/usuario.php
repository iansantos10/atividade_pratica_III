<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$usuario_logado = $_SESSION["usuario_id"];
$perfil_id = intval($_GET["id"] ?? 0);

if ($perfil_id <= 0) {
    header("Location: usuarios.php");
    exit;
}

$sqlUsuario = "
SELECT 
    u.*,
    (SELECT COUNT(*) FROM seguidores WHERE seguindo_id = u.id) AS total_seguidores,
    (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = u.id) AS total_seguindo,
    (SELECT COUNT(*) FROM posts WHERE usuario_id = u.id) AS total_posts,
    EXISTS(
        SELECT 1 FROM seguidores 
        WHERE seguidor_id = ? AND seguindo_id = u.id
    ) AS seguindo
FROM usuarios u
WHERE u.id = ?
";

$stmtUsuario = $conn->prepare($sqlUsuario);
$stmtUsuario->bind_param("ii", $usuario_logado, $perfil_id);
$stmtUsuario->execute();
$usuario = $stmtUsuario->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: usuarios.php");
    exit;
}

$sqlPosts = "
SELECT 
    p.id,
    p.conteudo,
    p.created_at,
    COUNT(c.id) AS total_curtidas
FROM posts p
LEFT JOIN curtidas c ON p.id = c.post_id
WHERE p.usuario_id = ?
GROUP BY p.id, p.conteudo, p.created_at
ORDER BY p.created_at DESC
";

$stmtPosts = $conn->prepare($sqlPosts);
$stmtPosts->bind_param("i", $perfil_id);
$stmtPosts->execute();
$posts = $stmtPosts->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil - VibeX</title>
    <link rel="stylesheet" href="css/usuarios.css">
</head>
<body>

<div class="usuarios-layout">

    <aside class="sidebar">
        <div class="logo-box">
            <img src="img/icon_vibex.png" class="logo">
            <h1>VibeX</h1>
            <p>Perfil do usuário</p>
        </div>

        <nav class="menu">
            <a href="feed.php" class="menu-item">🏠 Início</a>
            <a href="perfil.php" class="menu-item">👤 Meu Perfil</a>
            <a href="usuarios.php" class="menu-item active">🔎 Usuários</a>
            <a href="logout.php" class="menu-item">↩ Sair</a>
        </nav>
    </aside>

    <main class="usuarios-main">

        <div class="usuario-card">
            <img src="img/<?php echo htmlspecialchars($usuario["foto"] ?: "padrao.png"); ?>" class="foto-user">

            <h3><?php echo htmlspecialchars($usuario["nome"]); ?></h3>
            <p>@<?php echo htmlspecialchars($usuario["username"]); ?></p>

            <br>

            <p><strong>Seguidores:</strong> <?php echo $usuario["total_seguidores"]; ?></p>
            <p><strong>Seguindo:</strong> <?php echo $usuario["total_seguindo"]; ?></p>
            <p><strong>Posts:</strong> <?php echo $usuario["total_posts"]; ?></p>

            <?php if ($perfil_id != $usuario_logado) : ?>
                <div class="acoes">
                    <button class="btn-seguir" data-id="<?php echo $perfil_id; ?>">
                        <?php echo $usuario["seguindo"] ? "Deixar de seguir" : "Seguir"; ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <br>

        <div class="search-card">
            <h2>Posts de <?php echo htmlspecialchars($usuario["nome"]); ?></h2>
        </div>

        <div class="usuarios-grid">
            <?php if ($posts->num_rows == 0) : ?>
                <div class="usuario-card">
                    <p>Este usuário ainda não possui posts.</p>
                </div>
            <?php endif; ?>

            <?php while ($post = $posts->fetch_assoc()) : ?>
                <div class="usuario-card">
                    <p><?php echo htmlspecialchars($post["conteudo"]); ?></p>
                    <br>
                    <p>❤️ <?php echo $post["total_curtidas"]; ?> curtidas</p>
                </div>
            <?php endwhile; ?>
        </div>

    </main>

</div>

<script>
document.querySelectorAll(".btn-seguir").forEach(btn => {
    btn.addEventListener("click", function() {
        let usuario = this.dataset.id;
        let botao = this;

        fetch("seguir.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "usuario_id=" + usuario
        })
        .then(r => r.json())
        .then(data => {
            botao.innerText = data.seguindo ? "Deixar de seguir" : "Seguir";
            location.reload();
        });
    });
});
</script>

</body>
</html>