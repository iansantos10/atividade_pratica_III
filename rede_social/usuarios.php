<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION["usuario_id"];
$busca = trim($_GET["busca"] ?? "");

$sql = "
SELECT
    u.*,
    EXISTS(
        SELECT 1
        FROM seguidores s
        WHERE s.seguidor_id = ?
        AND s.seguindo_id = u.id
    ) AS seguindo
FROM usuarios u
WHERE 1=1
";

if (!empty($busca)) {
    $sql .= " AND (u.nome LIKE ? OR u.username LIKE ?)";
}

$sql .= " ORDER BY u.nome ASC";

$stmt = $conn->prepare($sql);

if (!empty($busca)) {
    $termo = "%" . $busca . "%";
    $stmt->bind_param("iss", $usuario_id, $termo, $termo);
} else {
    $stmt->bind_param("i", $usuario_id);
}

$stmt->execute();
$usuarios = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Usuários - VibeX</title>
    <link rel="stylesheet" href="css/usuarios.css">
</head>
<body>

<div class="usuarios-layout">

    <aside class="sidebar">
        <div class="logo-box">
            <img src="img/icon_vibex.png" class="logo" alt="Logo VibeX">
            <h1>VibeX</h1>
            <p>Explorar pessoas</p>
        </div>

        <nav class="menu">
            <a href="feed.php" class="menu-item">🏠 Início</a>
            <a href="perfil.php" class="menu-item">👤 Meu Perfil</a>
            <a href="usuarios.php" class="menu-item active">🔎 Usuários</a>
            <a href="logout.php" class="menu-item">↩ Sair</a>
        </nav>
    </aside>

    <main class="usuarios-main">

        <div class="search-card">
            <form method="GET">
                <input
                    type="text"
                    name="busca"
                    placeholder="Buscar por nome ou username..."
                    value="<?php echo htmlspecialchars($busca); ?>"
                >

                <button type="submit">Buscar</button>
            </form>
        </div>

        <div class="usuarios-grid">

            <?php if ($usuarios->num_rows == 0) : ?>
                <div class="usuario-card">
                    <p>Nenhum usuário encontrado.</p>
                </div>
            <?php endif; ?>

            <?php while ($user = $usuarios->fetch_assoc()) : ?>

                <div class="usuario-card">

                    <img
                        src="img/<?php echo htmlspecialchars($user["foto"] ?: "padrao.png"); ?>"
                        class="foto-user"
                        alt="Foto do usuário"
                    >

                    <h3><?php echo htmlspecialchars($user["nome"]); ?></h3>

                    <p>@<?php echo htmlspecialchars($user["username"]); ?></p>

                    <div class="acoes">

                        <a
                            href="usuario.php?id=<?php echo $user["id"]; ?>"
                            class="btn-ver"
                        >
                            Ver Perfil
                        </a>

                        <?php if ($user["id"] == $usuario_id) : ?>

                            <span class="btn-ver">Seu perfil</span>

                        <?php else : ?>

                            <button
                                type="button"
                                class="btn-seguir"
                                data-id="<?php echo $user["id"]; ?>"
                            >
                                <?php echo $user["seguindo"] ? "Deixar de seguir" : "Seguir"; ?>
                            </button>

                        <?php endif; ?>

                    </div>

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
            if (data.sucesso === false) {
                alert("Não foi possível realizar essa ação.");
                return;
            }

            botao.innerText = data.seguindo ? "Deixar de seguir" : "Seguir";
        });
    });
});
</script>

</body>
</html>