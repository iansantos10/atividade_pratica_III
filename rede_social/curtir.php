<?php
session_start();

require_once "config/database.php";

header("Content-Type: application/json");

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["sucesso" => false]);
    exit;
}

$usuario_id = $_SESSION["usuario_id"];
$post_id = intval($_POST["post_id"] ?? 0);

if ($post_id <= 0) {
    echo json_encode(["sucesso" => false]);
    exit;
}

$sql = "SELECT id FROM curtidas WHERE usuario_id = ? AND post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usuario_id, $post_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $sqlDelete = "DELETE FROM curtidas WHERE usuario_id = ? AND post_id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("ii", $usuario_id, $post_id);
    $stmtDelete->execute();

    $curtiu = false;
} else {
    $sqlInsert = "INSERT INTO curtidas (usuario_id, post_id) VALUES (?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $usuario_id, $post_id);
    $stmtInsert->execute();

    $curtiu = true;
}

$sqlTotal = "SELECT COUNT(*) AS total FROM curtidas WHERE post_id = ?";
$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->bind_param("i", $post_id);
$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_assoc()["total"];

echo json_encode([
    "sucesso" => true,
    "total" => $total,
    "curtiu" => $curtiu
]);