<?php

namespace App\Middleware;

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
            throw new \Exception("Token não informado.");
        }
        //verifica se o texto comeca com Bearer
        if (!str_starts_with($authorization, "Bearer")) {
            throw new \Exception("Formato de Token inválido.");
        }

        //remove os 7 caracteres iniciais
        $token = substr($authorization, 7);

        if ($token === "") {
            throw new \Exception("Token não informado.");
        }

        //enviado para validar e se estiver certo recebemos payload
        return $this->tokenService->validarToken($token);
    }
}