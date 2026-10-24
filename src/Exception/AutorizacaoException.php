<?php

namespace App\Exception;

use Exception;

//Representa um usuário autenticado tentando executar
//uma ação para a qual não possui permissão.
class AutorizacaoException extends Exception
{
}