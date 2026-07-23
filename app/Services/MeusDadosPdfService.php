<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class MeusDadosPdfService
{
    public function generate(array $data, string $cpfHash): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.meus-dados', [
            'data'    => $data,
            'cpfHash' => $cpfHash,
            'date'    => now()->format('d/m/Y H:i'),
        ])->setPaper('A4', 'portrait');
    }
}
