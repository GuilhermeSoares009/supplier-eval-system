<?php

namespace App\Http\Controllers;

use App\Models\RegistroRir;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function mensal(Request $request): JsonResponse
    {
        $mes = $request->get('mes');
        $mesDisponivel = RegistroRir::query()->max('mes_referencia');
        $mes = $mes ?: ($mesDisponivel ?: now()->format('Y-m'));

        $registros = RegistroRir::with('fornecedor')
            ->where('mes_referencia', $mes)
            ->get();

        $fornecedores = [];

        foreach ($registros as $registro) {
            $nome = $registro->fornecedor->nome;

            if (!isset($fornecedores[$nome])) {
                $fornecedores[$nome] = [
                    'fornecedor' => $nome,
                    'otimo' => 0,
                    'bom' => 0,
                    'regular' => 0,
                    'insatisfatorio' => 0,
                    'total' => 0,
                ];
            }

            switch ($registro->classificacao) {
                case 'Ótimo':
                    $fornecedores[$nome]['otimo']++;
                    break;
                case 'Bom':
                    $fornecedores[$nome]['bom']++;
                    break;
                case 'Regular':
                    $fornecedores[$nome]['regular']++;
                    break;
                default:
                    $fornecedores[$nome]['insatisfatorio']++;
                    break;
            }

            $fornecedores[$nome]['total']++;
        }

        $mesesDisponiveis = RegistroRir::query()
            ->select('mes_referencia')
            ->distinct()
            ->orderBy('mes_referencia')
            ->pluck('mes_referencia')
            ->values();

        return response()->json([
            'mes' => $mes,
            'meses' => $mesesDisponiveis,
            'fornecedores' => array_values($fornecedores),
        ]);
    }


}
