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
}