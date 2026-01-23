<?php

namespace App\Exports;

use App\Services\AvaliacaoConsolidadaService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class AvaliacaoConsolidadaExport implements FromArray, WithEvents, WithColumnWidths, WithTitle
{
    private int $ano;
    private AvaliacaoConsolidadaService $service;

    public function __construct(int $ano, AvaliacaoConsolidadaService $service)
    {
        $this->ano = $ano;
        $this->service = $service;
    }

    public function title(): string
    {
        return 'Avaliação';
    }

    public function array(): array
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 18,
            'D' => 15,
            'E' => 14,
            'F' => 18,
            'G' => 16,
            'H' => 14,
            'I' => 14,
            'J' => 14,
            'K' => 12,
            'L' => 20,
            'M' => 14,
            'N' => 16,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $avaliacoes = $this->service->avaliacoes($this->ano);
                $this->service->validar($avaliacoes);

                $sheet = $event->sheet->getDelegate();

                $sheet->getDefaultRowDimension()->setRowHeight(18);

                $this->renderCabecalho($sheet);
                $this->renderTabela($sheet, $avaliacoes);

                $ultimaLinha = 7 + $avaliacoes->count();
                $linhaLegenda = $ultimaLinha + 3;

                $this->renderLegenda($sheet, $linhaLegenda);

                $linhaResumo = $linhaLegenda + 6;
                $linhaResumo = $this->renderResumoMensal($sheet, $linhaResumo);

                $linhaRodape = $linhaResumo + 3;
                $this->renderRodape($sheet, $linhaRodape);
            },
        ];
    }

    private function renderCabecalho($sheet): void
    {
        $sheet->mergeCells('A1:B5');
        $logoPath = public_path('logo-laboratorio-cedro.png');

        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Laboratório Cedro');
            $drawing->setPath($logoPath);
            $drawing->setHeight(70);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
        } else {
            $sheet->setCellValue('A1', 'Laboratório Cedro');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        $sheet->mergeCells('C2:J3');
        $sheet->setCellValue('C2', 'SISTEMA DE GESTÃO DA QUALIDADE');
        $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('C4:J4');
        $sheet->setCellValue('C4', 'FORMULÁRIO');
        $sheet->getStyle('C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('C5:J5');
        $sheet->setCellValue('C5', 'RELATÓRIO DE INSPEÇÃO E RECEBIMENTO');
        $sheet->getStyle('C5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('C6:J6');
        $sheet->setCellValue('C6', 'Depto: GERAL');
        $sheet->getStyle('C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->setCellValue('K2', 'Código: FOR.GER.008');
        $sheet->setCellValue('K3', 'Revisão: 0');
        $sheet->setCellValue('K4', 'Página: 1 de 1');
    }

    private function renderTabela($sheet, $avaliacoes): void
    {
        $cabecalhos = [
            'A7' => 'Data de recebimento',
            'B7' => 'Fornecedor',
            'C7' => 'Nº do pedido',
            'D7' => 'Nº Nota Fiscal',
            'E7' => 'Total de itens do pedido',
            'F7' => 'Itens atendidos na nota',
            'G7' => 'Pedido x Nota fiscal',
            'H7' => 'Embalagem',
            'I7' => 'Temperatura',
            'J7' => 'Prazo de Entrega',
            'K7' => 'Validade',
            'L7' => 'Atendimento da transportadora',
            'M7' => 'Total Pontos',
            'N7' => 'Obs',
        ];

        foreach ($cabecalhos as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $estiloCabecalho = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];

        $estiloBordas = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $estiloAlinhamento = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A7:N7')->applyFromArray($estiloCabecalho);

        $linhaAtual = 8;
        foreach ($avaliacoes as $avaliacao) {
            $sheet->setCellValue("A{$linhaAtual}", Carbon::parse($avaliacao->data_recebimento)->format('d/m/Y'));
            $sheet->setCellValue("B{$linhaAtual}", $avaliacao->fornecedor->nome);
            $sheet->setCellValue("C{$linhaAtual}", $avaliacao->numero_pedido);
            $sheet->setCellValue("D{$linhaAtual}", $avaliacao->numero_nota_fiscal);
            $sheet->setCellValue("E{$linhaAtual}", $avaliacao->total_itens_pedido);
            $sheet->setCellValue("F{$linhaAtual}", $avaliacao->itens_atendidos_nota);
            $sheet->setCellValue("G{$linhaAtual}", $avaliacao->acuracidade);
            $sheet->setCellValue("H{$linhaAtual}", $avaliacao->criterio_embalagem);
            $sheet->setCellValue("I{$linhaAtual}", $avaliacao->criterio_temperatura);
            $sheet->setCellValue("J{$linhaAtual}", $avaliacao->criterio_prazo);
            $sheet->setCellValue("K{$linhaAtual}", $avaliacao->criterio_validade);
            $sheet->setCellValue("L{$linhaAtual}", $avaliacao->criterio_atendimento);
            $sheet->setCellValue("M{$linhaAtual}", $avaliacao->total_pontos);
            $sheet->setCellValue("N{$linhaAtual}", $avaliacao->classificacao);

            $this->styleAcuracidade($sheet, $linhaAtual, $avaliacao->acuracidade);
            $this->styleCriterios($sheet, $linhaAtual, [
                'H' => $avaliacao->criterio_embalagem,
                'I' => $avaliacao->criterio_temperatura,
                'J' => $avaliacao->criterio_prazo,
                'K' => $avaliacao->criterio_validade,
                'L' => $avaliacao->criterio_atendimento,
            ]);
            $this->styleTotalPontos($sheet, $linhaAtual, $avaliacao->total_pontos);
            $this->styleClassificacao($sheet, $linhaAtual, $avaliacao->classificacao);

            $linhaAtual++;
        }

        $ultimaLinha = $linhaAtual - 1;
        $sheet->getStyle("A7:N{$ultimaLinha}")->applyFromArray($estiloBordas);
        $sheet->getStyle("A8:N{$ultimaLinha}")->applyFromArray($estiloAlinhamento);
        $sheet->getStyle("M8:M{$ultimaLinha}")->getNumberFormat()->setFormatCode('0%');
    }

    private function styleAcuracidade($sheet, int $linha, float $valor): void
    {
        $cor = $valor == 1.0 ? 'C6EFCE' : 'FFC7CE';
        $sheet->getStyle("G{$linha}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cor);
    }

    private function styleCriterios($sheet, int $linha, array $criterios): void
    {
        foreach ($criterios as $col => $valor) {
            $cor = ((int) $valor === 1) ? 'C6EFCE' : 'FFC7CE';
            $sheet->getStyle("{$col}{$linha}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cor);
        }
    }

    private function styleTotalPontos($sheet, int $linha, float $valor): void
    {
        $cor = $valor >= 0.70 ? 'C6EFCE' : 'FFC7CE';
        $sheet->getStyle("M{$linha}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cor);
    }

    private function styleClassificacao($sheet, int $linha, string $classificacao): void
    {
        $cor = match ($classificacao) {
            'Ótimo' => '00B050',
            'Bom' => '0070C0',
            'Regular' => 'FFC000',
            default => 'FF0000',
        };

        $sheet->getStyle("N{$linha}")->getFont()->getColor()->setRGB($cor);
    }

    private function renderLegenda($sheet, int $linhaLegenda): void
    {
        $sheet->setCellValue("A{$linhaLegenda}", 'LEGENDA DE CLASSIFICAÇÃO');
        $sheet->mergeCells("A{$linhaLegenda}:C{$linhaLegenda}");
        $sheet->getStyle("A{$linhaLegenda}:C{$linhaLegenda}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE');

        $legendas = [
            ['90 a 100%', 'Ótimo', '00B050'],
            ['70 a 89%', 'Bom', '0070C0'],
            ['50 a 69%', 'Regular', 'FFC000'],
            ['Abaixo de 50%', 'Insatisfatório', 'FF0000'],
        ];

        foreach ($legendas as $index => $legenda) {
            $linha = $linhaLegenda + $index + 1;
            $sheet->setCellValue("A{$linha}", $legenda[0]);
            $sheet->setCellValue("B{$linha}", $legenda[1]);
            $sheet->getStyle("B{$linha}")->getFont()->getColor()->setRGB($legenda[2]);
        }
    }

    private function renderResumoMensal($sheet, int $linhaInicio): int
    {
        $resumo = $this->service->resumoMensal($this->ano);
        $linhaAtual = $linhaInicio;

        foreach ($resumo as $mes => $fornecedores) {
            $sheet->setCellValue("A{$linhaAtual}", strtoupper($mes));
            $linhaAtual++;

            $sheet->setCellValue("A{$linhaAtual}", 'FORNECEDOR');
            $sheet->setCellValue("B{$linhaAtual}", 'ÓTIMO 90 A 100');
            $sheet->setCellValue("C{$linhaAtual}", 'BOM 70 A 90');
            $sheet->setCellValue("D{$linhaAtual}", 'REGULAR 50 A 70');

            $sheet->getStyle("A{$linhaAtual}:D{$linhaAtual}")
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE');

            $linhaAtual++;

            foreach ($fornecedores as $fornecedor => $dados) {
                $sheet->setCellValue("A{$linhaAtual}", $fornecedor);
                $sheet->setCellValue("B{$linhaAtual}", $dados['Ótimo'] ?? 0);
                $sheet->setCellValue("C{$linhaAtual}", $dados['Bom'] ?? 0);
                $sheet->setCellValue("D{$linhaAtual}", $dados['Regular'] ?? 0);
                $linhaAtual++;
            }

            $linhaAtual += 2;
        }

        return $linhaAtual;
    }

    private function renderRodape($sheet, int $linhaRodape): void
    {
        $validadores = [
            ['Elaboração:', 'Nome do Elaborador', 'Data'],
            ['Verificação:', 'Nome do Verificador', 'Data'],
            ['Aprovação:', 'Nome do Aprovador', 'Data'],
        ];

        foreach ($validadores as $index => $validador) {
            $linha = $linhaRodape + $index;
            $sheet->setCellValue("A{$linha}", $validador[0]);
            $sheet->setCellValue("B{$linha}", $validador[1]);
            $sheet->setCellValue("C{$linha}", $validador[2]);
        }
    }
}
