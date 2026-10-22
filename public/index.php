<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../config/database.php";

use App\Repository\UsuarioRepository;
use App\Service\UsuarioService;
use App\Controller\UsuarioController;

header("Content-Type: application/json; charset=UTF-8");

//cria as dependencias
$repository = new UsuarioRepository($pdo);
$service = new UsuarioService($repository);
$controller = new UsuarioController($service);

//descobre o método http e a rota
$metodo = $_SERVER["REQUEST_METHOD"];
$rota = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

//Rota para cadastrar usuário
if ($metodo === "POST" && $rota === "/usuarios") {
    $controller->criar();
    exit;
}

//caso nenhuma resposta seja encontrada
http_response_code(404);
echo json_encode(["erro" => "Rota não encontrada."]);