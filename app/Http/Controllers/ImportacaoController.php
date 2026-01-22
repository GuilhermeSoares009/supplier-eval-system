<?php

namespace App\Http\Controllers;

use App\Services\RirImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportacaoController extends Controller
{
    public function importar(Request $request, RirImportService $service): JsonResponse
    {
        $request->validate([
            'arquivos' => ['required', 'array'],
            'arquivos.*' => ['file', 'mimes:xlsx,xls,csv'],
        ]);

        $resultado = $service->import($request->file('arquivos'));

        return response()->json($resultado);
    }
}
