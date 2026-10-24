<?php
/* Aqui ficam as regras de negócio dos chamados */

namespace App\Service;

use App\Exception\AutorizacaoException;
use App\Exception\RecursoNaoEncontradoException;
use App\Exception\ValidacaoException;
use App\Repository\ChamadoRepository;

class ChamadoService
{
    private ChamadoRepository $repository;

    public function __construct(ChamadoRepository $repository)
    {
        $this->repository = $repository;
    }

    // Método de criação do título e descrição do chamado
    public function criar(
        int $usuarioId,
        string $titulo,
        string $descricao
    ): int
    {
        $titulo = trim($titulo);
        $descricao = trim($descricao);

        // Valida o título
        if ($titulo === "") {
            throw new ValidacaoException("Título não pode estar vazio.");
        }

        if (mb_strlen($titulo, "UTF-8") > 150) {
            throw new ValidacaoException("Título deve ter no máximo 150 caracteres.");
        }

        // Valida a descrição
        if ($descricao === "") {
            throw new ValidacaoException("Descrição não pode estar vazia.");
        }

        return $this->repository->criar(
            $usuarioId,
            $titulo,
            $descricao
        );
    }

    // Delega a listagem ao Repository de acordo com o perfil
    public function listar(
        int $usuarioId,
        string $perfil
    ): array
    {
        if ($perfil === "admin") {
            return $this->repository->listarTodos();
        }

        return $this->repository->listarPorUsuario($usuarioId);
    }

    // Busca um chamado pelo ID respeitando o perfil do usuário
    public function buscarPorId(
        int $id,
        int $usuarioId,
        string $perfil
    ): array
    {
        if ($perfil === "admin") {
            $chamado = $this->repository->buscarPorIdGlobal($id);
        } else {
            $chamado = $this->repository->buscarPorId(
                $id,
                $usuarioId
            );
        }

        if ($chamado === null) {
            throw new RecursoNaoEncontradoException(
                "Chamado não encontrado."
            );
        }

        return $chamado;
    }

    // Atualiza título e/ou descrição de um chamado
    public function atualizar(
        int $id,
        int $usuarioId,
        ?string $titulo,
        ?string $descricao
    ): array
    {
        // Verifica se o chamado existe e pertence ao usuário
        $chamado = $this->repository->buscarPorId(
            $id,
            $usuarioId
        );

        if ($chamado === null) {
            throw new RecursoNaoEncontradoException(
                "Chamado não encontrado."
            );
        }

        // PATCH precisa receber pelo menos um campo
        if ($titulo === null && $descricao === null) {
            throw new ValidacaoException(
                "Informe ao menos um campo para atualizar."
            );
        }

        // Só valida título se ele foi enviado
        if ($titulo !== null) {
            $titulo = trim($titulo);

            if ($titulo === "") {
                throw new ValidacaoException(
                    "Título não pode estar vazio."
                );
            }

            if (mb_strlen($titulo, "UTF-8") > 150) {
                throw new ValidacaoException(
                    "Título deve ter no máximo 150 caracteres."
                );
            }
        }

        // Só valida descrição se ela foi enviada
        if ($descricao !== null) {
            $descricao = trim($descricao);

            if ($descricao === "") {
                throw new ValidacaoException(
                    "Descrição não pode estar vazia."
                );
            }
        }

        $this->repository->atualizar(
            $id,
            $usuarioId,
            $titulo,
            $descricao
        );

        return $this->repository->buscarPorId(
            $id,
            $usuarioId
        );
    }

    // Exclui um chamado pertencente ao usuário
    public function excluir(
        int $id,
        int $usuarioId
    ): void
    {
        $excluido = $this->repository->excluir(
            $id,
            $usuarioId
        );

        if (!$excluido) {
            throw new RecursoNaoEncontradoException(
                "Chamado não encontrado."
            );
        }
    }

    // Atualiza o status do chamado
    public function atualizarStatus(
        int $id,
        string $status,
        string $perfil
    ): array
    {
        // Apenas administradores podem alterar o status
        if ($perfil !== "admin") {
            throw new AutorizacaoException(
                "Acesso negado."
            );
        }

        $status = trim($status);

        $statusPermitidos = [
            "aberto",
            "em_andamento",
            "resolvido"
        ];

        if (!in_array($status, $statusPermitidos, true)) {
            throw new ValidacaoException(
                "Status inválido."
            );
        }

        $chamado = $this->repository->buscarPorIdGlobal($id);

        if ($chamado === null) {
            throw new RecursoNaoEncontradoException(
                "Chamado não encontrado."
            );
        }

        $this->repository->atualizarStatus(
            $id,
            $status
        );

        return $this->repository->buscarPorIdGlobal($id);
    }
}