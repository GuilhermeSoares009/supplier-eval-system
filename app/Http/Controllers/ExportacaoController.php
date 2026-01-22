<?php

namespace App\Http\Controllers;

use App\Exports\AvaliacaoConsolidadaExport;
use App\Services\AvaliacaoConsolidadaService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportacaoController extends Controller
{
    public function exportar(Request $request, AvaliacaoConsolidadaService $service)
    {
        $ano = (int) $request->get('ano', now()->year);

        return Excel::download(
            new AvaliacaoConsolidadaExport($ano, $service),
            sprintf('AVALIACAO_FORNECEDORES_%d.xlsx', $ano)
        );
    }
}
