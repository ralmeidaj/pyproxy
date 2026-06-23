<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidSplitException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(422, $message ?: 'Configuração de split inválida.');
    }
}
