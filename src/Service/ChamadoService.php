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

    //delega busca ao repository
    public function listarPorUsuario(int $usuarioId): array
    {
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
}