<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../config/database.php";

use App\Exception\AutenticacaoException;
use App\Repository\UsuarioRepository;
use App\Service\UsuarioService;
use App\Service\TokenService;
use App\Controller\UsuarioController;
use App\Middleware\AuthMiddleware;
use App\Repository\ChamadoRepository;
use App\Service\ChamadoService;
use App\Controller\ChamadoController;

header("Content-Type: application/json; charset=UTF-8");

//Centraliza a autenticação das rotas protegidas.
//Se o token for válido, retorna os dados do JWT.
//Se for inválido ou ausente, responde 401 e retorna null.
function autenticar(AuthMiddleware $authMiddleware): ?array// array ou null
{
    try {

        return $authMiddleware->autenticar();

    } catch (AutenticacaoException $erro) {
        http_response_code(401);
        echo json_encode(["erro" => $erro->getMessage()]);
        return null;
    }
}

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

    //rota para usuario autenticado buscar os próprios dados
    if ($metodo === "GET" && $rota ==="/me") {
        //Valida o token da rota protegida.
        //Se a autenticação falhar, a função já responde 401 e retorna null.        
        $dadosToken = autenticar($authMiddleware);

        //Se não houver usuário autenticado, encerra a execução da rota.
        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = (int) $dadosToken["sub"];

        $controller->me($usuarioId);

        exit;
    }

    //rota para abertura do chamado
    if ($metodo === "POST" && $rota === "/chamados") {

        $dadosToken = autenticar($authMiddleware);

        if ($dadosToken === null) {
            exit;
        }
            
        //O usuário não escolhe o usuario_id é a API que descobre
        $usuarioId = (int) $dadosToken["sub"];

        $chamadoController->criar($usuarioId);

        exit;

    }

    //método get para listar os chamados
    if ($metodo === "GET" && $rota === "/chamados") {

        $dadosToken = autenticar($authMiddleware);

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = (int) $dadosToken["sub"];
        $perfil = $dadosToken["perfil"];//verifica tambem o perfil para os casos do ADM

        $chamadoController->listar($usuarioId, $perfil);

        exit;

    }
    //preg_match = "o texto encaixa na máscara?" → 1 sim, 0 não, false erro.
    /*Ela verifica se a URL tem exatamente este formato:
    * /chamados/1
    * /chamados/25
    * /chamados/999*/
    if ($metodo === "GET" && preg_match("#^/chamados/([1-9][0-9]*)$#", $rota, $matches)) {
        
        $dadosToken = autenticar($authMiddleware);

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = (int) $dadosToken["sub"];
        $perfil = $dadosToken["perfil"];
        $chamadoId = (int) $matches[1];

        $chamadoController->buscarPorId(
            $chamadoId,
            $usuarioId,
            $perfil
        );

        exit;

    }

    if ($metodo === "PATCH" && preg_match("#^/chamados/([1-9][0-9]*)$#", $rota, $matches)) {
        
        $dadosToken = autenticar($authMiddleware);

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = (int) $dadosToken["sub"];
        $chamadoId = (int) $matches[1];

        $chamadoController->atualizar($chamadoId, $usuarioId);

        exit;

    }

    if ($metodo === "DELETE" && preg_match("#^/chamados/([1-9][0-9]*)$#", $rota, $matches)) {

        $dadosToken = autenticar($authMiddleware);

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = (int) $dadosToken["sub"];
        $chamadoId = (int) $matches[1];

        $chamadoController->excluir($chamadoId, $usuarioId);

        exit;
    }

    /*Essa regex aceita rotas como:
    /chamados/2/status
    /chamados/15/status 
    captura o ID em $matches[1]*/
    if ($metodo === "PATCH" && preg_match("#^/chamados/([1-9][0-9]*)/status$#", $rota, $matches)) {

        $dadosToken = autenticar($authMiddleware);

        if ($dadosToken === null) {
            exit;
        }

        $perfil = $dadosToken["perfil"];
        $chamadoId = (int) $matches[1];

        $chamadoController->atualizarStatus($chamadoId, $perfil);

        exit;

    }

    $metodosPermitidos = [
        "/usuarios" => ["POST"],
        "/login" => ["POST"],
        "/me" => ["GET"],
        "/chamados" => ["GET", "POST"]
    ];

    //pergunta se essa rota existe na nossa lista
    if (isset($metodosPermitidos[$rota])) {
        $permitidos = $metodosPermitidos[$rota];

        /*implode transforma, por exemplo: ["GET", "POST"] em GET, POST
        para podermos enviar Allow: GET, POST*/
        header("Allow: " . implode(", ", $permitidos));

        //405 Method Not Allowed
        //O servidor encontrou a rota/URL, mas o método HTTP usado na requisição
        //não é permitido para ela. Ex.: fazer POST em uma rota que só aceita GET.
        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido."]);
        exit;
    }
    
    //trata as rotas dinamicas
    if (preg_match("#^/chamados/([1-9][0-9]*)$#", $rota)) {

        header("Allow: GET, PATCH, DELETE");

        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido."]);
        exit;
    }

    //para status
    if (preg_match("#^/chamados/([1-9][0-9]*)/status$#", $rota)) {

        header("Allow: PATCH");

        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido."]);
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





