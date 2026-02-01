<?php

namespace App\Services;

class AvaliacaoReportDiff
{
    public function diff(array $manualIndex, array $geradoIndex): array
    {
        $meses = array_unique(array_merge(array_keys($manualIndex), array_keys($geradoIndex)));
        sort($meses);

        $resultado = [];

        foreach ($meses as $mes) {
            $manual = $manualIndex[$mes] ?? [];
            $gerado = $geradoIndex[$mes] ?? [];

            $missing = array_diff_key($manual, $gerado);
            $extra = array_diff_key($gerado, $manual);
            $countDiffs = [];

            foreach ($manual as $fornecedor => $counts) {
                if (!isset($gerado[$fornecedor])) {
                    continue;
                }
                if ($counts !== $gerado[$fornecedor]) {
                    $countDiffs[$fornecedor] = [
                        'manual' => $counts,
                        'gerado' => $gerado[$fornecedor],
                    ];
                }
            }

            $resultado[$mes] = [
                'missing' => $missing,
                'extra' => $extra,
                'count_diffs' => $countDiffs,
                'totais' => [
                    'manual' => count($manual),
                    'gerado' => count($gerado),
                    'missing' => count($missing),
                    'extra' => count($extra),
                    'count_diffs' => count($countDiffs),
                ],
            ];
        }

        return $resultado;
    }
}
