<?php

namespace App\Http\Controllers;

use App\Services\RirImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class ImportacaoController extends Controller
{
    public function importar(Request $request, RirImportService $service): JsonResponse
    {
        $request->validate([
            'arquivos' => ['required', 'array'],
            'arquivos.*' => ['file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $resultado = $service->import($request->file('arquivos'));

            return response()->json($resultado);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Falha ao processar os arquivos. Verifique o layout do RIR e tente novamente.',
            ], 500);
        }
    }
}
