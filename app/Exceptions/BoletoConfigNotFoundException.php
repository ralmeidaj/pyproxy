<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BoletoConfigNotFoundException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(422, $message ?: 'Configuração de boleto não encontrada para este tenant.');
    }
}
