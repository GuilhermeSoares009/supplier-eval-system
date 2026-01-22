<?php

namespace App\Services;

use App\Models\RegistroRir;
use Carbon\Carbon;

class AvaliacaoConsolidadaService
{
    public function gerar(int $ano): array
    {
        $prefixo = sprintf('%d-', $ano);
        $registros = RegistroRir::with('fornecedor')
            ->where('mes_referencia', 'like', $prefixo.'%')
            ->get();

        $dados = [];

        foreach ($registros as $registro) {
            $fornecedor = $registro->fornecedor->nome;
            $mes = Carbon::parse($registro->data_recebimento)->format('Y-m');

            if (!isset($dados[$fornecedor][$mes])) {
                $dados[$fornecedor][$mes] = [
                    'Ótimo' => 0,
                    'Bom' => 0,
                    'Regular' => 0,
                    'Insatisfatório' => 0,
                    'total' => 0,
                ];
            }

            $dados[$fornecedor][$mes][$registro->classificacao] = ($dados[$fornecedor][$mes][$registro->classificacao] ?? 0) + 1;
            $dados[$fornecedor][$mes]['total']++;
        }

        $linhas = [];
        foreach ($dados as $fornecedor => $meses) {
            foreach ($this->mesesDoAno($ano) as $mes) {
                $linha = $meses[$mes] ?? [
                    'Ótimo' => 0,
                    'Bom' => 0,
                    'Regular' => 0,
                    'Insatisfatório' => 0,
                    'total' => 0,
                ];

                $linhas[] = [
                    'fornecedor' => $fornecedor,
                    'mes' => $mes,
                    'otimo' => $linha['Ótimo'],
                    'bom' => $linha['Bom'],
                    'regular' => $linha['Regular'],
                    'insatisfatorio' => $linha['Insatisfatório'],
                    'total' => $linha['total'],
                ];
            }
        }

        return $linhas;
    }

    private function mesesDoAno(int $ano): array
    {
        return array_map(function ($mes) use ($ano) {
            return sprintf('%d-%02d', $ano, $mes);
        }, range(1, 12));
    }
}
