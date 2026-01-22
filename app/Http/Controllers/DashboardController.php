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

    public function heatmap(Request $request): JsonResponse
    {
        $ano = (int) $request->get('ano', 0);
        if ($ano === 0) {
            $mesDisponivel = RegistroRir::query()->max('mes_referencia');
            $ano = $mesDisponivel ? (int) substr($mesDisponivel, 0, 4) : now()->year;
        }
        $prefixo = sprintf('%d-', $ano);

        $registros = RegistroRir::with('fornecedor')
            ->where('mes_referencia', 'like', $prefixo.'%')
            ->get();

        $matriz = [];

        foreach ($registros as $registro) {
            $nome = $registro->fornecedor->nome;
            $mes = Carbon::parse($registro->data_recebimento)->format('M');
            $mes = $this->mesAbreviado($mes);

            if (!isset($matriz[$nome])) {
                $matriz[$nome] = [];
            }

            if (!isset($matriz[$nome][$mes])) {
                $matriz[$nome][$mes] = [
                    'Ótimo' => 0,
                    'Bom' => 0,
                    'Regular' => 0,
                    'Insatisfatório' => 0,
                ];
            }

            $matriz[$nome][$mes][$registro->classificacao] = ($matriz[$nome][$mes][$registro->classificacao] ?? 0) + 1;
        }

        $resultado = [];
        foreach ($matriz as $fornecedor => $meses) {
            $linha = [
                'fornecedor' => $fornecedor,
                'meses' => [],
            ];

            foreach ($this->listaMeses() as $mes) {
                $linha['meses'][$mes] = $this->dominante($meses[$mes] ?? []);
            }

            $resultado[] = $linha;
        }

        return response()->json([
            'ano' => $ano,
            'fornecedores' => $resultado,
        ]);
    }

    private function dominante(array $contagem): ?string
    {
        if (empty($contagem)) {
            return null;
        }

        $ordem = ['Insatisfatório', 'Regular', 'Bom', 'Ótimo'];
        $maior = -1;
        $status = null;

        foreach ($ordem as $classe) {
            $valor = $contagem[$classe] ?? 0;
            if ($valor > $maior) {
                $maior = $valor;
                $status = $classe;
            }
        }

        return $status;
    }

    private function listaMeses(): array
    {
        return ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    }

    private function mesAbreviado(string $mesIngles): string
    {
        return match ($mesIngles) {
            'Jan' => 'Jan',
            'Feb' => 'Fev',
            'Mar' => 'Mar',
            'Apr' => 'Abr',
            'May' => 'Mai',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Ago',
            'Sep' => 'Set',
            'Oct' => 'Out',
            'Nov' => 'Nov',
            'Dec' => 'Dez',
            default => $mesIngles,
        };
    }
}
