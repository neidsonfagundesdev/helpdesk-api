<?php

namespace App\Service;

use App\Exception\AutenticacaoException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

//classe criada para gerar o token
//esse token serve para identificar o usuario
//JWT = JSON Web Tokens
class TokenService
{
    private string $chave;

    public function __construct()
    {
        $chave = getenv("JWT_SECRET");
        //se for diferente da chave retorna erro
        if (!$chave) {
            throw new Exception("JWT_SECRET não configurada.");
        }
        //se encontrou o JWT_SECRET no ambiente guarda na propriedade $chave
        $this->chave = $chave;
    }

    //gera o token JWT
    public function gerarToken(array $usuario): string
    {   
        /*retorna um timestamp Unix: um número inteiro representando os segundos decorridos 
        desde 1º de janeiro de 1970 (00:00:00 UTC).*/ 
        $agora = time();

        $payload = [
            "sub" => $usuario["id"],//sub-> subject significa, "quem é o dono desse token?"
            "perfil" => $usuario["perfil"],//perfil servirá para autorizaacao "admin" ou "user"
            "iat" => $agora,//issued at-> momento em que o token foi emitido
            "exp" => $agora + 3600/*expiration time. 
                                  3600 segundos = 1 hora. 
                                  Depois de criado o token vale por 1 hora e depois expira*/
        ];

        return JWT::encode(//Json Web Token
            $payload,//contém: dados do usuário, expiração, etc...
            $this->chave,//Chave secreta que ASSINA o token (só o servidor tem)
            "HS256"//O algoritmo usado para assinar (HMAC + SHA-256)
        );
    }

    //valida o token recebido
    public function validarToken(string $token): array
    {   
        //valida a estrutura do token
        $this->validarEstruturaToken($token);

        //recebe o token, decodifica, verifica a assinatura e varifica a expiracao
        //se estiver certo devolve payload
        try {
            $dados = JWT::decode(
                $token,
                //A key diz ao PHP valide esse token usando esta chave e aceite o algoritmo HS256
                //Isso é importante porque não devemos aceitar qualquer algoritmo escrito no token
                new Key($this->chave, "HS256")
            );
            //retorna $dados com sub, perfil, iat, exp...
            return (array) $dados;
        //se estiver errado lanca a excecão
        } catch (\UnexpectedValueException $erro) {
            throw new AutenticacaoException("Token inválido ou expirado.");
        }
    }

    // Valida a estrutura básica do JWT antes da decodificação.
    // Tokens malformados são tratados como erro de autenticação (401).
    private function validarEstruturaToken(string $token): void
    {
        $partes = explode(".", $token);

        if (count($partes) !== 3) {
            throw new AutenticacaoException("Token inválido.");
        }

        foreach ($partes as $parte) {
            if (
                $parte === ""
                || !preg_match('/^[A-Za-z0-9_-]+$/', $parte)
            ) {
                throw new AutenticacaoException("Token inválido.");
            }
        }

        // Decodifica o header do JWT.
        $headerBase64 = strtr($partes[0], "-_", "+/");

        $resto = strlen($headerBase64) % 4;

        if ($resto !== 0) {
            $headerBase64 .= str_repeat("=", 4 - $resto);
        }

        $headerJson = base64_decode($headerBase64, true);

        if ($headerJson === false) {
            throw new AutenticacaoException("Token inválido.");
        }

        try {
            // Sem o parâmetro true para preservar objeto JSON como objeto PHP.
            $header = json_decode(
                $headerJson,
                false,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $erro) {
            throw new AutenticacaoException("Token inválido.");
        }

        // O header JWT precisa ser um objeto.
        if (!is_object($header)) {
            throw new AutenticacaoException("Token inválido.");
        }

        // Este projeto aceita somente HS256.
        if (
            !isset($header->alg)
            || !is_string($header->alg)
            || $header->alg !== "HS256"
        ) {
            throw new AutenticacaoException("Token inválido.");
        }

        // Se kid estiver presente, precisa ser texto.
        if (
            property_exists($header, "kid")
            && !is_string($header->kid)
        ) {
            throw new AutenticacaoException("Token inválido.");
        }

        // Decodifica e valida o payload.
        $payloadBase64 = strtr($partes[1], "-_", "+/");

        $resto = strlen($payloadBase64) % 4;

        if ($resto !== 0) {
            $payloadBase64 .= str_repeat("=", 4 - $resto);
        }

        $payloadJson = base64_decode($payloadBase64, true);

        if ($payloadJson === false) {
            throw new AutenticacaoException("Token inválido.");
        }

        try {
            $payload = json_decode(
                $payloadJson,
                false,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $erro) {
            throw new AutenticacaoException("Token inválido.");
        }

        // O payload JWT também precisa ser um objeto.
        if (!is_object($payload)) {
            throw new AutenticacaoException("Token inválido.");
        }
    }

}