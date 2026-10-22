<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../config/database.php";

use App\Repository\UsuarioRepository;
use App\Service\UsuarioService;
use App\Service\TokenService;
use App\Controller\UsuarioController;
use App\Middleware\AuthMiddleware;

header("Content-Type: application/json; charset=UTF-8");

try{
    //cria as dependencias
    $repository = new UsuarioRepository($pdo);
    $tokenService = new TokenService();
    $service = new UsuarioService($repository, $tokenService);//gera JWT no login
    $controller = new UsuarioController($service);
    $authMiddleware = new AuthMiddleware($tokenService);//valida JWT recebido

    //descobre o método http e a rota
    $metodo = $_SERVER["REQUEST_METHOD"];
    $rota = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

    //Rota para cadastrar usuário
    if ($metodo === "POST" && $rota === "/usuarios") {
        $controller->criar();
        exit;
    }

    //Rota para login
    if ($metodo === "POST" && $rota === "/login") {
        $controller->login();
        exit;
    }

    if ($metodo === "GET" && $rota ==="/me") {
        try {
            $dadosToken = $authMiddleware->autenticar();

            $usuarioId = (int) $dadosToken["sub"];

            $controller->me($usuarioId);

        } catch (\Exception $erro) {
            http_response_code(401);
            echo json_encode(["erro" => $erro->getMessage()]);
        }

        exit;
    }

    //caso nenhuma resposta seja encontrada
    http_response_code(404);
    echo json_encode(["erro" => "Rota não encontrada."]);

  /*
  Throwable é mais amplo que Exception
  Ele consegue capturar tanto Exception
  quanto vários erros fatais do PHP que também implementam Throwable
  */
} catch (\Throwable $erro) {
    error_log($erro->getMessage());

    http_response_code(500);

    echo json_encode(["erro" => "Erro interno do servidor."]);
}





