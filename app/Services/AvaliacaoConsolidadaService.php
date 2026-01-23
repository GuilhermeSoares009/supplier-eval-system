<?php

namespace App\Services;

use App\Models\RegistroRir;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class AvaliacaoConsolidadaService
{
    public function avaliacoes(int $ano): Collection
    {
        $prefixo = sprintf('%d-', $ano);

        return RegistroRir::with('fornecedor')
            ->where('mes_referencia', 'like', $prefixo.'%')
            ->orderBy('data_recebimento')
            ->get();
    }

    public function validar(Collection $avaliacoes): void
    {
        if ($avaliacoes->isEmpty()) {
            throw new RuntimeException('Não há avaliações para exportar.');
        }

        foreach ($avaliacoes as $avaliacao) {
            if ($avaliacao->itens_atendidos_nota > $avaliacao->total_itens_pedido) {
                throw new RuntimeException("Erro: Itens atendidos maior que total de itens para {$avaliacao->fornecedor->nome}");
            }

            $criterios = [
                'criterio_embalagem',
                'criterio_temperatura',
                'criterio_prazo',
                'criterio_validade',
                'criterio_atendimento',
            ];

            foreach ($criterios as $criterio) {
                if (!in_array($avaliacao->{$criterio}, [0, 1], true)) {
                    throw new RuntimeException("Erro: {$criterio} deve ser 0 ou 1 para {$avaliacao->fornecedor->nome}");
                }
            }

            $acuracidade = $avaliacao->total_itens_pedido > 0
                ? $avaliacao->itens_atendidos_nota / $avaliacao->total_itens_pedido
                : 0;

            $totalCalculado = (
                $acuracidade
                + $avaliacao->criterio_embalagem
                + $avaliacao->criterio_temperatura
                + $avaliacao->criterio_prazo
                + $avaliacao->criterio_validade
                + $avaliacao->criterio_atendimento
            ) / 6;

            if ($avaliacao->total_pontos === null || abs($totalCalculado - $avaliacao->total_pontos) > 0.01) {
                $avaliacao->acuracidade = $acuracidade;
                $avaliacao->total_pontos = $totalCalculado;
                $avaliacao->nota_total = $totalCalculado;
                $avaliacao->classificacao = $this->classificar($totalCalculado);
            }
        }
    }

    private function classificar(float $totalPontos): string
    {
        if ($totalPontos >= 0.90) {
            return 'Ótimo';
        }

        if ($totalPontos >= 0.70) {
            return 'Bom';
        }

        if ($totalPontos >= 0.50) {
            return 'Regular';
        }

        return 'Insatisfatório';
    }

    public function resumoMensal(int $ano): array
    {
        $avaliacoes = $this->avaliacoes($ano);
        $resumo = [];

        foreach ($avaliacoes as $avaliacao) {
            $mes = Carbon::parse($avaliacao->data_recebimento)->locale('pt_BR')->isoFormat('MMMM');
            $fornecedor = $avaliacao->fornecedor->nome;
            $classificacao = $avaliacao->classificacao;

            if (!isset($resumo[$mes][$fornecedor])) {
                $resumo[$mes][$fornecedor] = [
                    'Ótimo' => 0,
                    'Bom' => 0,
                    'Regular' => 0,
                    'Insatisfatório' => 0,
                ];
            }

            $resumo[$mes][$fornecedor][$classificacao] = ($resumo[$mes][$fornecedor][$classificacao] ?? 0) + 1;
        }

        return $resumo;
    }
}
