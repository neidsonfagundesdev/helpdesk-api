<?php

namespace App\Service;

use App\Repository\UsuarioRepository;
use Exception;

class UsuarioService
{
    private UsuarioRepository $repository;

    public function __construct(UsuarioRepository $repository)
    {
        $this->repository = $repository;
    }

    public function criar(string $nome, string $email, string $senha): int
    {
        //valida nome
        $nome = trim($nome);

        if ($nome === "") {
            throw new Exception("Nome não pode estar vazio.");
        }

        if (mb_strlen($nome, "UTF-8") > 150) {
            throw new Exception("Nome deve ter no máximo 150 caracteres.");
        }
        //valida email
        //transforma tudo em minusculo e remove espacos
        $email = strtolower(trim($email));

        if ($email === "") {
            throw new Exception("E-mail não pode estar vazio.");
        }
        /*
        filter_var() significa basicamente:
        “PHP, verifique se esse valor atende determinado filtro.”
        Aqui o filtro é: FILTER_VALIDATE_EMAIL
        */
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("E-mail inválido.");
        }
        //verifica email duplicado
        $usuarioExistente = $this->repository->buscarPorEmail($email);

        if ($usuarioExistente !== null) {
            throw new Exception("E-mail já cadastrado.");
        }

        //valida senha
        if (strlen($senha) < 8) {
            throw new Exception("Senha deve possuir no mínimo 8 caracteres.");
        }
        //Gera hash. É uma "impressão digital" do texto. Serve para esconder senhas de forma segura. 
        // Diferente de criptografia, o hash NÃO tem volta (não dá para "desembaralhar").
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        //chama repository
        return $this->repository->criar($nome, $email, $senhaHash);
    }
}