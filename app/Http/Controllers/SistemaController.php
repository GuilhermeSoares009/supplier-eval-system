<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SistemaController extends Controller
{
    public function limpar(): JsonResponse
    {
        DB::statement('DELETE FROM registros_rir');
        DB::statement('DELETE FROM fornecedores');
        DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('registros_rir', 'fornecedores')");

        return response()->json([
            'status' => 'ok',
        ]);
    }
}
