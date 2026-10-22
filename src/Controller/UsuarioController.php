<?php

namespace App\Controller;

use App\Service\UsuarioService;
use Exception;
use PDOException;

class usuarioController
{
    private UsuarioService $service;

    public function __construct(UsuarioService $service)
    {
        $this->service = $service;
    }

    public function criar(): void
    {
        $corpo = file_get_contents("php://input");

        try {
            $dadosObjeto = json_decode(
                $corpo,
                false,
                512,
                JSON_THROW_ON_ERROR
            );

        } catch (\JsonException $erro) {
            http_response_code(400);
            echo json_encode(["erro" => "JSON inválido."]);
            exit;
        }

        if (!is_object($dadosObjeto)) {
            http_response_code(400);
            echo json_encode(["erro" => "O corpo deve ser um objeto JSON."]);
            exit;
        }

        $dados = (array) $dadosObjeto;

        $nome = $dados["nome"] ?? null;
        $email = $dados["email"] ?? null;
        $senha = $dados["senha"] ?? null;

        if (!is_string($nome)) {
            http_response_code(400);
            echo json_encode(["erro" => "Nome deve ser um texto."]);
            exit;
        }

        if (!is_string($email)) {
            http_response_code(400);
            echo json_encode(["erro" => "E-mail deve ser um texto."]);
            exit;
        }

        if (!is_string($senha)) {
            http_response_code(400);
            echo json_encode(["erro" => "Senha deve ser um texto."]);
            exit;
        }

        try {
            $novoId = $this->service->criar(
                $nome,
                $email,
                $senha
            );

            http_response_code(201);

            echo json_encode([
                "id" => $novoId,
                "nome" => $nome,
                "email" => strtolower(trim($email))
            ]);

        } catch (PDOException $erro) {
            error_log($erro->getMessage());
            http_response_code(500);
            echo json_encode(["erro" => "Erro interno do servidor."]);

        } catch (Exception $erro) {
            http_response_code(400);
            echo json_encode(["erro" => $erro->getMessage()]);
        }
    }

    public function login(): void
    {
        $corpo = file_get_contents("php://input");

        try {
            $dadosObjeto = json_decode(
                $corpo,
                false,
                512,
                JSON_THROW_ON_ERROR
            );

        } catch (\JsonException $erro) {
            http_response_code(400);
            echo json_encode(["erro" => "JSON inválido."]);
            exit;
        }

        if (!is_object($dadosObjeto)) {
            http_response_code(400);
            echo json_encode(["erro" => "O corpo deve ser um objeto JSON."]);
            exit;
        }

        $dados = (array) $dadosObjeto;

        $email = $dados["email"] ?? null;
        $senha = $dados["senha"] ?? null;

        if (!is_string($email)) {
            http_response_code(400);
            echo json_encode(["erro" => "E-mail deve ser um texto."]);
            exit;
        }

        if (!is_string($senha)) {
            http_response_code(400);
            echo json_encode(["erro" => "Senha deve ser um texto."]);
            exit;
        }

        try {
            
            $resultado = $this->service->login($email, $senha);

            http_response_code(200);

            echo json_encode(["mensagem" => "Login realizado com sucesso.", 
            "usuario" => $resultado["usuario"],
            "token" => $resultado["token"]
            ]);

        } catch (Exception $erro) {
            http_response_code(401); //Unauthorized -> Você não conseguiu provar quem é.
            echo json_encode(["erro" => $erro->getMessage()]);
        }
    }

    //me -> mostre quem eu sou!
    //recebe o id autenticado, chama o service,
    // devolve 200 com o usuario, caso não exista mais devolve 404
    public function me(int $usuarioId): void
    {
        try {
            $usuario = $this->service->buscarUsuarioAutenticado($usuarioId);

            http_response_code(200);

            echo json_encode(["usuario" => $usuario]);

        } catch (Exception $erro) {
            http_response_code(404);
            echo json_encode(["erro" => $erro->getMessage()]);
        }
    }
}