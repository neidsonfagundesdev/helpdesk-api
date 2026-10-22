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

    public function listar(int $usuarioId): void
    {
        try {
            $chamados = $this->service->listarPorUsuario($usuarioId);

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

}