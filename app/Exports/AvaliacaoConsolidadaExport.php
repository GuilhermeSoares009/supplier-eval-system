<?php

namespace App\Exports;

use App\Services\AvaliacaoConsolidadaService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class AvaliacaoConsolidadaExport implements FromView
{
    private int $ano;
    private AvaliacaoConsolidadaService $service;

    public function __construct(int $ano, AvaliacaoConsolidadaService $service)
    {
        $this->ano = $ano;
        $this->service = $service;
    }

    public function view(): View
    {
        return view('exports.avaliacao_consolidada', [
            'ano' => $this->ano,
            'linhas' => $this->service->gerar($this->ano),
        ]);
    }
}
