<?php

namespace App\Middleware;

use App\Exception\AutenticacaoException;
use App\Service\TokenService;

class AuthMiddleware
{
    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    //método para ler o header
    public function autenticar(): array
    {
        $authorization = $_SERVER["HTTP_AUTHORIZATION"] ?? null;

        if ($authorization === null) {
            throw new AutenticacaoException("Token não informado.");
        }
        // Verifica se o header segue o formato: Bearer TOKEN
        if (!str_starts_with($authorization, "Bearer ")) {
            throw new AutenticacaoException("Formato de Token inválido.");
        }

        // Remove "Bearer " e elimina espaços extras
        $token = trim(substr($authorization, 7));

        if ($token === "") {
            throw new AutenticacaoException("Token não informado.");
        }

        //Valida os dados essenciais do JWT antes de liberar a requisição.
        //Confirma se o token possui um ID de usuário válido e um perfil permitido.
        $dadosToken = $this->tokenService->validarToken($token);

        // Confirma se o perfil presente no token é permitido.
        if (
            !isset($dadosToken["sub"])
            || !is_numeric($dadosToken["sub"])
            || (int) $dadosToken["sub"] <= 0
        ) {
            throw new AutenticacaoException("Token inválido.");
        }

        if (
            !isset($dadosToken["perfil"])
            || !is_string($dadosToken["perfil"])
            || !in_array($dadosToken["perfil"], ["user", "admin"], true)
        ) {
            throw new AutenticacaoException("Token inválido.");
        }

        //Normaliza o ID do usuário para inteiro antes de retornar os dados do token.
        $dadosToken["sub"] = (int) $dadosToken["sub"];

        return $dadosToken;
    }
}