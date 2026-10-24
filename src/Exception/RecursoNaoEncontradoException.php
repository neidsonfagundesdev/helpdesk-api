<?php

namespace App\Exception;

use Exception;

//Representa a tentativa de acessar um recurso que não existe
//ou que não está disponível para o usuário atual.
class RecursoNaoEncontradoException extends Exception
{
}