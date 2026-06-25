<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Payproxy API',
    description: 'API pública para emissão de boletos bancários com split de pagamento. Autenticação via API Key no header `X-Api-Key`.',
    contact: new OA\Contact(name: 'Ciberian', email: 'suporte@ciberian.com.br'),
)]
#[OA\Server(url: '/api/v1', description: 'Produção')]
#[OA\SecurityScheme(
    securityScheme: 'ApiKey',
    type: 'apiKey',
    in: 'header',
    name: 'X-Api-Key',
    description: 'API Key gerada no backoffice Payproxy. Formato: `ppx_<40 chars>`.',
)]
#[OA\Tag(name: 'Boletos', description: 'Emissão, consulta e cancelamento de boletos')]
class ApiInfo {}
