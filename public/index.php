<?php

require_once __DIR__ . "/../vendor/autoload.php";

use App\Exception\AutenticacaoException;
use App\Repository\UsuarioRepository;
use App\Service\UsuarioService;
use App\Service\TokenService;
use App\Controller\UsuarioController;
use App\Middleware\AuthMiddleware;
use App\Repository\ChamadoRepository;
use App\Service\ChamadoService;
use App\Controller\ChamadoController;

// Define desde o início que todas as respostas da API são JSON.
header("Content-Type: application/json; charset=UTF-8");


// Centraliza a autenticação das rotas protegidas.
// Se o token for válido, retorna os dados do JWT.
// Se for inválido ou ausente, responde 401 e retorna null.
function autenticar(AuthMiddleware $authMiddleware): ?array
{
    try {
        return $authMiddleware->autenticar();

    } catch (AutenticacaoException $erro) {
        http_response_code(401);

        echo json_encode([
            "erro" => $erro->getMessage()
        ]);

        return null;
    }
}


// Descobre primeiro o método HTTP e a rota.
// Isso acontece antes de inicializar banco, JWT e demais dependências.
$metodo = $_SERVER["REQUEST_METHOD"];
$rota = parse_url(
    $_SERVER["REQUEST_URI"],
    PHP_URL_PATH
);


// Métodos permitidos nas rotas fixas.
$metodosPermitidos = [
    "/usuarios" => ["POST"],
    "/login" => ["POST"],
    "/me" => ["GET"],
    "/chamados" => ["GET", "POST"]
];


// Verifica método inválido nas rotas fixas.
if (isset($metodosPermitidos[$rota])) {

    $permitidos = $metodosPermitidos[$rota];

    if (!in_array($metodo, $permitidos, true)) {

        header(
            "Allow: " . implode(", ", $permitidos)
        );

        http_response_code(405);

        echo json_encode([
            "erro" => "Método não permitido."
        ]);

        exit;
    }
}


// Verifica as rotas dinâmicas de chamados.
// Exemplo:
// /chamados/1
// /chamados/25
if (
    preg_match(
        "#^/chamados/([1-9][0-9]*)$#",
        $rota
    )
) {

    $permitidos = [
        "GET",
        "PATCH",
        "DELETE"
    ];

    if (!in_array($metodo, $permitidos, true)) {

        header(
            "Allow: " . implode(", ", $permitidos)
        );

        http_response_code(405);

        echo json_encode([
            "erro" => "Método não permitido."
        ]);

        exit;
    }
}


// Verifica a rota dinâmica de alteração de status.
// Exemplo:
// /chamados/2/status
if (
    preg_match(
        "#^/chamados/([1-9][0-9]*)/status$#",
        $rota
    )
) {

    if ($metodo !== "PATCH") {

        header("Allow: PATCH");

        http_response_code(405);

        echo json_encode([
            "erro" => "Método não permitido."
        ]);

        exit;
    }
}


// Verifica se a rota realmente existe.
// Somente depois disso inicializamos banco, JWT, controllers etc.
$rotaConhecida =
    isset($metodosPermitidos[$rota])
    || preg_match(
        "#^/chamados/([1-9][0-9]*)$#",
        $rota
    )
    || preg_match(
        "#^/chamados/([1-9][0-9]*)/status$#",
        $rota
    );


if (!$rotaConhecida) {

    http_response_code(404);

    echo json_encode([
        "erro" => "Rota não encontrada."
    ]);

    exit;
}


try {

    /*
    Somente agora carregamos a conexão com o banco
    e criamos as dependências da aplicação.
    */
    require_once __DIR__ . "/../config/database.php";


    // Dependências de usuários
    $repository = new UsuarioRepository($pdo);

    $tokenService = new TokenService();

    $service = new UsuarioService(
        $repository,
        $tokenService
    );

    $controller = new UsuarioController(
        $service
    );


    // Middleware de autenticação
    $authMiddleware = new AuthMiddleware(
        $tokenService
    );


    // Dependências dos chamados
    $chamadoRepository = new ChamadoRepository(
        $pdo
    );

    $chamadoService = new ChamadoService(
        $chamadoRepository
    );

    $chamadoController = new ChamadoController(
        $chamadoService
    );


    // Rota para cadastrar usuário
    if (
        $metodo === "POST"
        && $rota === "/usuarios"
    ) {

        $controller->criar();

        exit;
    }


    // Rota para login
    if (
        $metodo === "POST"
        && $rota === "/login"
    ) {

        $controller->login();

        exit;
    }


    // Usuário autenticado busca os próprios dados
    if (
        $metodo === "GET"
        && $rota === "/me"
    ) {

        $dadosToken = autenticar(
            $authMiddleware
        );

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = $dadosToken["sub"];

        $controller->me(
            $usuarioId
        );

        exit;
    }


    // Abertura de chamado
    if (
        $metodo === "POST"
        && $rota === "/chamados"
    ) {

        $dadosToken = autenticar(
            $authMiddleware
        );

        if ($dadosToken === null) {
            exit;
        }

        // O usuário não escolhe o usuario_id.
        // A API utiliza o ID presente no JWT.
        $usuarioId = $dadosToken["sub"];

        $chamadoController->criar(
            $usuarioId
        );

        exit;
    }


    // Lista os chamados
    if (
        $metodo === "GET"
        && $rota === "/chamados"
    ) {

        $dadosToken = autenticar(
            $authMiddleware
        );

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = $dadosToken["sub"];
        $perfil = $dadosToken["perfil"];

        $chamadoController->listar(
            $usuarioId,
            $perfil
        );

        exit;
    }


    // Busca chamado por ID
    if (
        $metodo === "GET"
        && preg_match(
            "#^/chamados/([1-9][0-9]*)$#",
            $rota,
            $matches
        )
    ) {

        $dadosToken = autenticar(
            $authMiddleware
        );

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = $dadosToken["sub"];
        $perfil = $dadosToken["perfil"];

        $chamadoId = (int) $matches[1];

        $chamadoController->buscarPorId(
            $chamadoId,
            $usuarioId,
            $perfil
        );

        exit;
    }


    // Atualiza título e/ou descrição
    if (
        $metodo === "PATCH"
        && preg_match(
            "#^/chamados/([1-9][0-9]*)$#",
            $rota,
            $matches
        )
    ) {

        $dadosToken = autenticar(
            $authMiddleware
        );

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = $dadosToken["sub"];

        $chamadoId = (int) $matches[1];

        $chamadoController->atualizar(
            $chamadoId,
            $usuarioId
        );

        exit;
    }


    // Exclui chamado
    if (
        $metodo === "DELETE"
        && preg_match(
            "#^/chamados/([1-9][0-9]*)$#",
            $rota,
            $matches
        )
    ) {

        $dadosToken = autenticar(
            $authMiddleware
        );

        if ($dadosToken === null) {
            exit;
        }

        $usuarioId = $dadosToken["sub"];

        $chamadoId = (int) $matches[1];

        $chamadoController->excluir(
            $chamadoId,
            $usuarioId
        );

        exit;
    }


    // Atualiza status do chamado
    if (
        $metodo === "PATCH"
        && preg_match(
            "#^/chamados/([1-9][0-9]*)/status$#",
            $rota,
            $matches
        )
    ) {

        $dadosToken = autenticar(
            $authMiddleware
        );

        if ($dadosToken === null) {
            exit;
        }

        $perfil = $dadosToken["perfil"];

        $chamadoId = (int) $matches[1];

        $chamadoController->atualizarStatus(
            $chamadoId,
            $perfil
        );

        exit;
    }


    /*
    Segurança adicional:
    teoricamente não devemos chegar aqui porque
    rota e método já foram verificados anteriormente.
    */
    http_response_code(404);

    echo json_encode([
        "erro" => "Rota não encontrada."
    ]);


/*
Throwable é mais amplo que Exception.

Captura erros inesperados que não foram tratados
pelas camadas anteriores e devolve apenas uma
mensagem genérica para o cliente.
*/
} catch (\Throwable $erro) {

    error_log(
        $erro->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "erro" => "Erro interno do servidor."
    ]);
}