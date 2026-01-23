<?php

namespace App\Http\Controllers;

use App\Services\RirImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportacaoController extends Controller
{
    public function importar(Request $request, RirImportService $service): JsonResponse
    {
        $importId = (string) Str::uuid();

        $request->validate([
            'arquivos' => ['required', 'array'],
            'arquivos.*' => ['file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $files = $request->file('arquivos');

            Log::channel('rir_import')->info('Iniciando importação RIR', [
                'import_id' => $importId,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'files' => collect($files)->map(fn ($f) => [
                    'original_name' => method_exists($f, 'getClientOriginalName') ? $f->getClientOriginalName() : null,
                    'mime' => method_exists($f, 'getMimeType') ? $f->getMimeType() : null,
                    'size' => method_exists($f, 'getSize') ? $f->getSize() : null,
                ])->all(),
            ]);

            $resultado = $service->import($files);

            Log::channel('rir_import')->info('Importação RIR finalizada', [
                'import_id' => $importId,
                'resultado' => $resultado,
            ]);

            return response()->json($resultado);
        } catch (RuntimeException $exception) {
            Log::channel('rir_import')->warning('Falha validável na importação RIR', [
                'import_id' => $importId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
                'error_id' => $importId,
            ], 422);
        } catch (Throwable $exception) {
            Log::channel('rir_import')->error('Erro inesperado na importação RIR', [
                'import_id' => $importId,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Falha ao processar os arquivos. Verifique o layout do RIR e tente novamente.',
                'error_id' => $importId,
            ], 500);
        }
    }
}
