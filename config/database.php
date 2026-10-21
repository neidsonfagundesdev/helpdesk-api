<?php

$host = getenv("MYSQLHOST") ?: "localhost";
$porta = getenv("MYSQLPORT") ?: "3306";
$banco = getenv("MYSQLDATABASE") ?: "helpdesk";
$usuario = getenv("MYSQLUSER") ?: "root";
$senha = getenv("MYSQLPASSWORD") ?: "";

$dsn = "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4";

try {
    $pdo = new PDO(
        $dsn,
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    
} catch (PDOException $erro) {
    error_log($erro->getMessage());

    http_response_code(500);

    echo json_encode(["erro" => "Erro interno do servidor."]);

    exit;
}