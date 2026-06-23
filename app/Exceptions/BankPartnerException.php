<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BankPartnerException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(502, $message ?: 'Erro na comunicação com o parceiro bancário.');
    }
}
