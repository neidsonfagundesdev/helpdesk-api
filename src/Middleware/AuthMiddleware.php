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

        // O esquema Bearer não diferencia maiúsculas de minúsculas.
        // Aceita, por exemplo: Bearer, bearer ou BEARER.
        if (strncasecmp($authorization, "Bearer ", 7) !== 0) {
            throw new AutenticacaoException("Formato de token inválido.");
        }

        // Remove "Bearer " e elimina espaços extras
        $token = trim(substr($authorization, 7));

        if ($token === "") {
            throw new AutenticacaoException("Token não informado.");
        }

        //Valida os dados essenciais do JWT antes de liberar a requisição.
        //Confirma se o token possui um ID de usuário válido e um perfil permitido.
        $dadosToken = $this->tokenService->validarToken($token);

        //Valida os claims de tempo obrigatórios do JWT: emissão (iat) e expiração (exp).
        //Garante que o token possui data de emissão e expiração válidas.
        //Isso evita aceitar tokens sem prazo de validade ou com claims de tempo inválidos.
        if (
            !isset($dadosToken["exp"])
            || !is_int($dadosToken["exp"])
            || $dadosToken["exp"] <= time()
        ) {
            throw new AutenticacaoException("Token inválido ou expirado.");
        }

        // Valida o momento de emissão do token.
        // O token não pode ter sido emitido no futuro
        // e o iat precisa ser anterior à expiração.
        $agora = time();

        if (
            !isset($dadosToken["iat"])
            || !is_int($dadosToken["iat"])
            || $dadosToken["iat"] <= 0
            || $dadosToken["iat"] > $agora
            || $dadosToken["iat"] >= $dadosToken["exp"]
        ) {
            throw new AutenticacaoException("Token inválido.");
        }

        //Garante que o identificador do usuário seja um inteiro positivo.
        //Evita aceitar valores como 6.5 ou "6e0" e convertê-los para outro ID.
        if (
            !isset($dadosToken["sub"])
            || !is_int($dadosToken["sub"])
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