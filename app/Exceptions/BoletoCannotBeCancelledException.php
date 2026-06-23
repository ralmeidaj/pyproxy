<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BoletoCannotBeCancelledException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(422, $message ?: 'O boleto não pode ser cancelado no status atual.');
    }
}
