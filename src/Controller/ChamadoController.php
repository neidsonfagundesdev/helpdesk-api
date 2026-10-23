<?php

namespace App\Controller;

use App\Service\ChamadoService;
use Exception;
use PDOException;

class ChamadoController
{
    private ChamadoService $service;

    public function __construct(ChamadoService $service)
    {
        $this->service = $service;
    }

    //método criar
    // : void → este método NÃO retorna valor (só executa a ação).
    /*
    O Controller não lê usuario_id do JSON.
    Ele vai receber o ID que já veio do JWT:
    */
    public function criar(int $usuarioId): void
    {
        $corpo = file_get_contents("php://input");

        try{
            $dadosObjeto = json_decode(
                $corpo,
                false,
                512,
                JSON_THROW_ON_ERROR
            );

        } catch (\JsonException $erro) {
            http_response_code(400);
            echo json_encode(["erro" => "JSON inválido."]);
            return;
        }

        if (!is_object($dadosObjeto)) {
            http_response_code(400);
            echo json_encode(["erro" => "Corpo deve ser um objeto JSON."]);
            return;
        }

        $dados = (array) $dadosObjeto;

        $titulo = $dados["titulo"] ?? null;
        $descricao = $dados["descricao"] ?? null;

        if (!is_string($titulo)) {
            http_response_code(400);
            echo json_encode(["erro" => "Título deve ser um texto."]);
            return;
        }

        if (!is_string($descricao)) {
            http_response_code(400);
            echo json_encode(["erro" => "Descrição deve ser um texto."]);
            return;
        }

        try {
            $novoId = $this->service->criar(
                $usuarioId,
                $titulo,
                $descricao
            );

            http_response_code(201);

            echo json_encode([
                "id" => $novoId,
                "titulo" => trim($titulo),
                "descricao" => trim($descricao),
                "status" => "aberto"
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

    public function listar(int $usuarioId, string $perfil): void
    {
        try {
            $chamados = $this->service->listar($usuarioId, $perfil);

            http_response_code(200);

            echo json_encode(["chamados" => $chamados]);

        } catch (PDOException $erro) {
            error_log($erro->getMessage());
            http_response_code(500);
            echo json_encode(["erro" => "Erro interno do servidor."]);
        }
    }

    public function buscarPorId(int $id, int $usuarioId): void
    {
        try {
            $chamado = $this->service->buscarPorId(
                $id,
                $usuarioId
            );

            http_response_code(200);

            echo json_encode(["chamado" => $chamado]);

        } catch (PDOException $erro) {
            error_log($erro->getMessage());
            http_response_code(500);
            echo json_encode(["erro" => "Erro interno do servidor."]);

        } catch (Exception $erro) {
            http_response_code(404);
            echo json_encode(["erro" => $erro->getMessage()]);
        }
    }

    public function atualizar(int $id, int $usuarioId): void
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
            return;
        }

        if (!is_object($dadosObjeto)) {
            http_response_code(400);
            echo json_encode(["erro" => "O corpo deve ser um objeto JSON."]);
            return;
        }

        $dados = (array) $dadosObjeto;

        $titulo = null;
        $descricao = null;

        if (array_key_exists("titulo", $dados)) {
            if (!is_string($dados["titulo"])) {
                http_response_code(400);
                echo json_encode(["erro" => "Título deve ser um texto."]);
                return;
            }

            $titulo = $dados["titulo"];
        }

        if (array_key_exists("descricao", $dados)) {
            if (!is_string($dados["descricao"])) {
                http_response_code(400);
                echo json_encode(["erro" => "Descrição deve ser um texto."]);
                return;
            }

            $descricao = $dados["descricao"];
        }

        try {
            $chamado = $this->service->atualizar(
                $id,
                $usuarioId,
                $titulo,
                $descricao
            );

            http_response_code(200);

            echo json_encode(["chamado" => $chamado]);

        } catch (PDOException $erro) {
            error_log($erro->getMessage());

            http_response_code(500);

            echo json_encode(["erro" => "Erro interno do servidor."]);

        } catch (Exception $erro) {
            //operador ternario, se a mensagem que retorna for exatamente
            //a string comparada status 404 se não 400
            $status = $erro->getMessage() === "Chamado não encontrado." ? 404 : 400;

            http_response_code($status);

            echo json_encode(["erro" => $erro->getMessage()]);
        }
    }

    public function excluir(int $id, int $usuarioId): void
    {
        try {
            $this->service->excluir($id, $usuarioId);
            //204 -> No Content significa: a requisição deu certo, mas não há corpo de resposta.
            http_response_code(204);

        } catch (PDOException $erro) {
            error_log($erro->getMessage());
            http_response_code(500);
            echo json_encode(["erro" => "Erro interno do servidor."]);

        } catch (Exception $erro) {
            http_response_code(404);
            echo json_encode(["erro" => $erro->getMessage()]);
        }
    }
    
    //atualiza status
    public function atualizarStatus(int $id, string $perfil): void
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
            return;
        }

        if (!is_object($dadosObjeto)) {
            http_response_code(400);
            echo json_encode(["erro" => "O corpo deve ser um objeto JSON."]);
            return;
        }

        $dados = (array) $dadosObjeto;

        $status = $dados["status"] ?? null;

        if (!is_string($status)) {
            http_response_code(400);
            echo json_encode(["erro" => "Status deve ser um texto."]);
            return;
        }

        try {
            $chamado = $this->service->atualizarStatus(
                $id,
                $status,
                $perfil
            );

            http_response_code(200);

            echo json_encode([
                "chamado" => $chamado
            ]);

        } catch (PDOException $erro) {
            error_log($erro->getMessage());
            http_response_code(500);
            echo json_encode(["erro" => "Erro interno do servidor."]);

        } catch (Exception $erro) {

            if ($erro->getMessage() === "Acesso negado.") {
                http_response_code(403);//→ 403 Forbidden → user tentando alterar status

            } elseif ($erro->getMessage() === "Chamado não encontrado.") {
                http_response_code(404);

            } else {
                http_response_code(400);
            }

            echo json_encode(["erro" => $erro->getMessage()]);
        }
    }

}