<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../config/database.php";

use App\Repository\UsuarioRepository;
use App\Service\UsuarioService;
use App\Service\TokenService;
use App\Controller\UsuarioController;
use App\Middleware\AuthMiddleware;
use App\Repository\ChamadoRepository;
use App\Service\ChamadoService;
use App\Controller\ChamadoController;

header("Content-Type: application/json; charset=UTF-8");

try{
    //cria as dependencias
    $repository = new UsuarioRepository($pdo);
    $tokenService = new TokenService();
    $service = new UsuarioService($repository, $tokenService);//gera JWT no login
    $controller = new UsuarioController($service);
    $authMiddleware = new AuthMiddleware($tokenService);//valida JWT recebido
    $chamadoRepository = new ChamadoRepository($pdo);
    $chamadoService = new ChamadoService($chamadoRepository);
    $chamadoController = new ChamadoController($chamadoService);

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

    //rota para abertura do chamado
    if ($metodo === "POST" && $rota === "/chamados") {

        try {
            $dadosToken = $authMiddleware->autenticar();
            
            //O usuário não escolhe o usuario_id é a API que descobre
            $usuarioId = (int) $dadosToken["sub"];

            $chamadoController->criar($usuarioId);

        } catch (\Exception $erro) {
            http_response_code(401);
            echo json_encode(["erro" => $erro->getMessage()]);
        }

        exit;

    }

    //método get para listar os chamados
    if ($metodo === "GET" && $rota === "/chamados") {

        try {
            $dadosToken = $authMiddleware->autenticar();

            $usuarioId = (int) $dadosToken["sub"];
            $perfil = $dadosToken["perfil"];//verifica tambem o perfil para os casos do ADM

            $chamadoController->listar($usuarioId, $perfil);

        } catch (\Exception $erro) {
            http_response_code(401);
            echo json_encode(["erro" => $erro->getMessage()]);
        }

        exit;

    }
    //preg_match = "o texto encaixa na máscara?" → 1 sim, 0 não, false erro.
    /*Ela verifica se a URL tem exatamente este formato:
    * /chamados/1
    * /chamados/25
    * /chamados/999*/
    if ($metodo === "GET" && preg_match("#^/chamados/([1-9][0-9]*)$#", $rota, $matches)) {

        try {
            $dadosToken = $authMiddleware->autenticar();

            $usuarioId = (int) $dadosToken["sub"];
            $perfil = $dadosToken["perfil"];
            $chamadoId = (int) $matches[1];

            $chamadoController->buscarPorId(
                $chamadoId,
                $usuarioId,
                $perfil
            );

        } catch (\Exception $erro) {
            http_response_code(401);

            echo json_encode([
                "erro" => $erro->getMessage()
            ]);
        }

        exit;

    }

    if ($metodo === "PATCH" && preg_match("#^/chamados/([1-9][0-9]*)$#", $rota, $matches)) {

        try {
            $dadosToken = $authMiddleware->autenticar();

            $usuarioId = (int) $dadosToken["sub"];
            $chamadoId = (int) $matches[1];

            $chamadoController->atualizar($chamadoId, $usuarioId);

        } catch (\Exception $erro) {
            http_response_code(401);
            echo json_encode(["erro" => $erro->getMessage()]);
        }

        exit;

    }

    if ($metodo === "DELETE" && preg_match("#^/chamados/([1-9][0-9]*)$#", $rota, $matches)) {

        try {
            $dadosToken = $authMiddleware->autenticar();

            $usuarioId = (int) $dadosToken["sub"];
            $chamadoId = (int) $matches[1];

            $chamadoController->excluir($chamadoId, $usuarioId);

        } catch (\Exception $erro) {
            http_response_code(401);
            echo json_encode(["erro" => $erro->getMessage()]);
        }

        exit;
    }

    /*Essa regex aceita rotas como:
    /chamados/2/status
    /chamados/15/status 
    captura o ID em $matches[1]*/
    if ($metodo === "PATCH" && preg_match("#^/chamados/([1-9][0-9]*)/status$#", $rota, $matches)) {

        try {
            $dadosToken = $authMiddleware->autenticar();

            $perfil = $dadosToken["perfil"];
            $chamadoId = (int) $matches[1];

            $chamadoController->atualizarStatus(
                $chamadoId,
                $perfil
            );

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





