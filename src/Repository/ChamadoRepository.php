<?php

namespace App\Repository;

use PDO;

//logica da classe: cuidar do acesso a tabela chamados
class ChamadoRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    //método para inserir no banco titulo e descricao do chamado
    public function criar(int $usuarioId, string $titulo, string $descricao): int
    {
        $sql = "INSERT INTO chamados (usuario_id, titulo, descricao) 
        VALUES (:usuario_id, :titulo, :descricao)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            "usuario_id" => $usuarioId,
            "titulo" => $titulo,
            "descricao" => $descricao
        ]);

        //lastInsertId() -> // Pega o ID que o banco gerou no INSERT do usuário
        return (int) $this->pdo->lastInsertId();
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT id, usuario_id, titulo, descricao, status, created_at, updated_at
        FROM chamados
        WHERE usuario_id = :usuario_id
        ORDER BY id DESC";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute(["usuario_id" => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        $sql = "SELECT id, usuario_id, titulo, descricao, status, created_at, updated_at
                FROM chamados
                WHERE id = :id
                AND usuario_id = :usuario_id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            "id" => $id,
            "usuario_id" => $usuarioId
        ]);

        $chamado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $chamado ?: null;
    }

        //O ? antes do tipo indica “nullable” (aceita null)
        //agora o método retorna bool: 
        //true  → alguma linha foi alterada, false → nenhuma linha foi alterada
        public function atualizar(int $id, int $usuarioId, ?string $titulo, ?string $descricao): bool
    {   
        //COALESCE permite PATCH parcial
        //ex.: alterar o titulo e manter a descricao antiga ou alterar a descricao e manter o titulo
        $sql = "UPDATE chamados
                SET titulo = COALESCE(:titulo, titulo),
                    descricao = COALESCE(:descricao, descricao)
                WHERE id = :id
                AND usuario_id = :usuario_id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            "titulo" => $titulo,
            "descricao" => $descricao,
            "id" => $id,
            "usuario_id" => $usuarioId
        ]);

        //$stmt->rowCount() devolve a quantidade de linhas afetadas pela execução
        return $stmt->rowCount() > 0;
    }

    //metodo para exclusao de chamado
        public function excluir(int $id, int $usuarioId): bool
    {
        $sql = "DELETE FROM chamados
                WHERE id = :id
                AND usuario_id = :usuario_id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            "id" => $id,
            "usuario_id" => $usuarioId
        ]);

        return $stmt->rowCount() > 0;
    }
}