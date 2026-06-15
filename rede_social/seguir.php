<?php
session_start();
require_once "config/database.php";

header("Content-Type: application/json");

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["sucesso" => false]);
    exit;
}

$seguidor = $_SESSION["usuario_id"];
$seguindo = intval($_POST["usuario_id"] ?? 0);

if ($seguindo <= 0 || $seguindo == $seguidor) {
    echo json_encode(["sucesso" => false]);
    exit;
}

$sql = "SELECT id FROM seguidores WHERE seguidor_id = ? AND seguindo_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $seguidor, $seguindo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $sql = "DELETE FROM seguidores WHERE seguidor_id = ? AND seguindo_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $seguidor, $seguindo);
    $stmt->execute();

    echo json_encode(["sucesso" => true, "seguindo" => false]);
} else {
    $sql = "INSERT INTO seguidores (seguidor_id, seguindo_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $seguidor, $seguindo);
    $stmt->execute();

    echo json_encode(["sucesso" => true, "seguindo" => true]);
}