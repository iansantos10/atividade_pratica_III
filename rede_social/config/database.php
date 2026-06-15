<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "rede_social";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Erro ao conectar ao banco: " . $conn->connect_error);
}

$conn->set_charset("utf8");