<?php
                     /*Aqui ficam as regras de negócio antes do INSERT */

namespace App\Service;

use App\Repository\ChamadoRepository;
use Exception;

class ChamadoService
{
    private ChamadoRepository $repository;

    public function __construct(ChamadoRepository $repository)
    {
        $this->repository = $repository;
    }

    //método de criacao do titulo e descricão do chamado
    public function criar(int $usuarioId, string $titulo, string $descricao): int
    {
        $titulo = trim($titulo);
        $descricao = trim($descricao);

        //valida o titulo
        if ($titulo === "") {
            throw new Exception("Título não pode estar vazio.");
        }

        if (mb_strlen($titulo, "UTF-8") > 150) {
            throw new Exception("Título deve ter no máximo 150 caracteres.");
        }

        //valida a descricao
        if ($descricao === "") {
            throw new Exception("Descrição não pode estar vazia.");
        }
        
        //retorna a criacao apos as validacoes
        return $this->repository->criar($usuarioId, $titulo, $descricao);
    }

    //delega busca ao repository de acordo com o perfil
    public function listar(int $usuarioId, string $perfil): array
    {
        if ($perfil === "admin") {//ADM lista todos os chamados
            return $this->repository->listarTodos();
        }
        
        //usuario comum lista todos os chamados ligados a ele pelo id
        return $this->repository->listarPorUsuario($usuarioId);
    }

    public function buscarPorId(int $id, int $usuarioId): array
    {
        $chamado = $this->repository->buscarPorId($id, $usuarioId);

        if ($chamado === null) {
            throw new Exception("Chamado não encontrado.");
        }

        return $chamado;
    }

        public function atualizar(int $id, int $usuarioId, ?string $titulo, ?string $descricao): array
    {
        // Verifica se o chamado existe e pertence ao usuário
        $chamado = $this->repository->buscarPorId($id, $usuarioId);

        if ($chamado === null) {
            throw new Exception("Chamado não encontrado.");
        }

        // PATCH precisa receber pelo menos um campo
        if ($titulo === null && $descricao === null) {
            throw new Exception("Informe ao menos um campo para atualizar.");
        }

        // Só valida título se ele foi enviado
        if ($titulo !== null) {
            $titulo = trim($titulo);

            if ($titulo === "") {
                throw new Exception("Título não pode estar vazio.");
            }

            if (mb_strlen($titulo, "UTF-8") > 150) {
                throw new Exception("Título deve ter no máximo 150 caracteres.");
            }
        }

        // Só valida descrição se ela foi enviada
        if ($descricao !== null) {
            $descricao = trim($descricao);

            if ($descricao === "") {
                throw new Exception("Descrição não pode estar vazia.");
            }
        }

        $this->repository->atualizar(
            $id,
            $usuarioId,
            $titulo,
            $descricao
        );

        // Busca novamente para devolver o chamado já atualizado
        return $this->repository->buscarPorId($id, $usuarioId);
    }

    //metodo para excluir chamado. 
    //vai transformar o false do chamado repository em "chamado nao encontrado"
        public function excluir(int $id, int $usuarioId): void
    {
        $excluido = $this->repository->excluir($id, $usuarioId);

        /*Repository retorna false
        → chamado não existe ou pertence a outro usuário
        → Service lança "Chamado não encontrado." */
        if (!$excluido) {
            throw new Exception("Chamado não encontrado.");
        }
    }

    //atualiza o status
    public function atualizarStatus(int $id, string $status, string $perfil): array
    {
        if ($perfil !== "admin") {
            throw new Exception("Acesso negado.");
        }

        $status = trim($status);

        $statusPermitidos = [
            "aberto",
            "em_andamento",
            "resolvido"
        ];

        //O valor recebido está dentro desta lista? Se não, retorna o erro.
        if (!in_array($status, $statusPermitidos, true)) {
            throw new Exception("Status inválido.");
        }

        $chamado = $this->repository->buscarPorIdGlobal($id);

        if ($chamado === null) {
            throw new Exception("Chamado não encontrado.");
        }

        $this->repository->atualizarStatus(
            $id,
            $status
        );

        return $this->repository->buscarPorIdGlobal($id);
    }

}