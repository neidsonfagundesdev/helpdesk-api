<?php

namespace App\Service;

use App\Exception\AutenticacaoException;
use App\Exception\RecursoNaoEncontradoException;
use App\Exception\ValidacaoException;
use App\Repository\UsuarioRepository;

class UsuarioService
{
    private UsuarioRepository $repository;
    private TokenService $tokenService;

    public function __construct(UsuarioRepository $repository, TokenService $tokenService)
    {
        $this->repository = $repository;
        $this->tokenService = $tokenService;
    }

    //cria com os dados recebidos    
    public function criar(string $nome, string $email, string $senha): int
    {
        //valida nome
        $nome = trim($nome);

        if ($nome === "") {
            throw new ValidacaoException("Nome não pode estar vazio.");
        }

        if (mb_strlen($nome, "UTF-8") > 150) {
            throw new ValidacaoException("Nome deve ter no máximo 150 caracteres.");
        }
        //valida email
        //transforma tudo em minusculo e remove espacos
        $email = strtolower(trim($email));

        if ($email === "") {
            throw new ValidacaoException("E-mail não pode estar vazio.");
        }
        /*
        filter_var() significa basicamente:
        “PHP, verifique se esse valor atende determinado filtro.”
        Aqui o filtro é: FILTER_VALIDATE_EMAIL
        */
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidacaoException("E-mail inválido.");
        }
        //verifica email duplicado
        $usuarioExistente = $this->repository->buscarPorEmail($email);

        if ($usuarioExistente !== null) {
            throw new ValidacaoException("E-mail já cadastrado.");
        }

        //valida senha
        if (strlen($senha) < 8) {
            throw new Exception("Senha deve possuir no mínimo 8 caracteres.");
        }

        // O bcrypt considera no máximo 72 bytes da senha.
        // Evita aceitar uma senha maior e ignorar silenciosamente o restante.
        if (strlen($senha) > 72) {
            throw new ValidacaoException(
                "Senha deve possuir no máximo 72 bytes."
            );
        }

        //Gera hash. É uma "impressão digital" do texto. Serve para esconder senhas de forma segura. 
        // Diferente de criptografia, o hash NÃO tem volta (não dá para "desembaralhar").
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        //chama repository
        return $this->repository->criar($nome, $email, $senhaHash);
    }

    //valida o login por usuario e senha
    public function login(string $email, string $senha): array
    {
        $email = strtolower(trim($email));

        $usuario = $this->repository->buscarPorEmail($email);

        if ($usuario === null) {
            throw new AutenticacaoException("E-mail ou senha inválidos.");
        }
        //password_verify compara a senha em texto puro com o hash salvo no banco.
        //retorna um valor booleano
        if (!password_verify($senha, $usuario["senha"])) {
            throw new AutenticacaoException("E-mail ou senha inválidos.");
        }
        //gera o token    
        $token = $this->tokenService->gerarToken($usuario);

        //remove a senha da variavel
        unset($usuario["senha"]);

        //retorna usuario e token    
        return ["usuario" => $usuario, "token" => $token];
    }

    //busca por usuario autenticado
    //caso nao exista retorna a mensagem. Se existir, retorna os dados.
    public function buscarUsuarioAutenticado(int $id): array
    {
        $usuario = $this->repository->buscarPorId($id);

        if ($usuario === null) {
            throw new RecursoNaoEncontradoException("Usuário não encontrado.");
        }

        return $usuario;
    }
}