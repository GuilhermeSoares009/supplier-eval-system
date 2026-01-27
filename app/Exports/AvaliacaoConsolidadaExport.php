<?php

namespace App\Exports;

use App\Models\RegistroRir;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AvaliacaoConsolidadaExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected string|int $ano;

    public function __construct(string|int $ano)
    {
        $this->ano = $ano;
    }

    public function collection()
    {
        return RegistroRir::with('fornecedor')
            ->where('mes_referencia', 'like', "{$this->ano}-%")
            ->orderBy('mes_referencia')
            ->orderBy('fornecedor_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Mês',
            'Fornecedor',
            'Avaliação',
            'Itens Positivos',
            'Itens a Melhorar',
            'Observação',
        ];
    }

    public function map($registro): array
    {
        $positivos = [];
        $negativos = [];

        // === TRADUÇÃO DE CRITÉRIOS ===

        // 1. Prazo / Pontualidade
        if ($registro->criterio_prazo == 1) {
            $positivos[] = 'Pontualidade no atendimento/Prazo de entrega';
        } else {
            $negativos[] = 'Atrasos na entrega';
        }

        // 2. Embalagem
        if ($registro->criterio_embalagem == 1) {
            $positivos[] = 'Embalagem adequada';
        } else {
            $negativos[] = 'Problemas com Embalagem';
        }

        // 3. Atendimento / Suporte
        if ($registro->criterio_atendimento == 1) {
            $positivos[] = 'Disponibilidade do Suporte';
        } else {
            $negativos[] = 'Dificuldade de Contato/Suporte';
        }

        // 4. Validade
        if ($registro->criterio_validade == 1) {
            $positivos[] = 'Validade adequada';
        } else {
            $negativos[] = 'Validade curta/vencida';
        }

        // 5. Temperatura
        if ($registro->criterio_temperatura == 1) {
            $positivos[] = 'Temperatura adequada';
        } else {
            $negativos[] = 'Desvio de Temperatura';
        }

        // 6. Acuracidade (Quantidade)
        if ($registro->acuracidade >= 1.0) {
            $positivos[] = 'Quantidade conforme pedido';
        } else {
            $negativos[] = 'Divergência na quantidade';
        }

        // === MONTAGEM DAS STRINGS ===

        // Junta todos os positivos com vírgula
        $strPositivos = implode(', ', $positivos);

        // Se for Ótimo (100%) e não houver negativos reais, deixa em branco
        // Caso contrário, junta os negativos com vírgula
        $strNegativos = '';
        if (!empty($negativos)) {
            $strNegativos = implode(', ', $negativos);
        }

        return [
            $registro->mes_referencia,
            $registro->fornecedor->nome ?? 'N/A',
            mb_strtoupper($registro->classificacao ?? 'N/A'),
            $strPositivos,
            $strNegativos,
            $registro->classificacao ?? '', // Observação (pode ser preenchida no futuro)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Cabeçalho com fundo azul e texto branco
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F81BD']
                ]
            ],
        ];
    }
}
