<?php

namespace App\Exception;

use Exception;

//Representa erros causados por dados inválidos enviados pelo cliente.
//Esses erros normalmente resultam em HTTP 400.
class ValidacaoException extends Exception
{
}