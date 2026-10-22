<?php

namespace App\Repository; //endereco da classe dentro do projeto

use PDO;//essa classe vai usar a classe PDO do PHP

class UsuarioRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    //cria o usuario no banco
    public function criar(string $nome, string $email, string $senhaHash): int
    {
        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            "nome" => $nome,
            "email" => $email,
            "senha" => $senhaHash
        ]);

        return (int) $this->pdo->lastInsertId();
    }
    //busca pelo email
    public function buscarPorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM usuarios WHERE email = :email";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute(["email" => $email]);
        //pega uma linha do resultado e devolve como array associativo
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        //Se não encontrar nenhum usuário, fetch() retorna false
        return $usuario ?: null;
    }

    //busca por id
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT id, nome, email, perfil, created_at FROM usuarios WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            "id" => $id
        ]);

        $usuario =$stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

}