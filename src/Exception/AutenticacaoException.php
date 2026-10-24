<?php

namespace App\Exception;

use Exception;

//Exceção específica para falhas de autenticação.
//Permite diferenciar credenciais inválidas (401)
//de erros internos inesperados (500).
class AutenticacaoException extends Exception
{
}