<?php

namespace App\Http\Controllers;

use App\Exports\AvaliacaoConsolidadaExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class ExportacaoController extends Controller
{
    public function exportar(Request $request)
    {
        $ano = (int) $request->get('ano', now()->year);
        try {
            return Excel::download(
                new AvaliacaoConsolidadaExport($ano),
                'AVALIAÇÃO DE FORNECEDORES.xlsx'
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Falha ao gerar o arquivo de avaliação.',
            ], 500);
        }
    }
}
