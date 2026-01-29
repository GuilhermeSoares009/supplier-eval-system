<?php

namespace App\Exports;

use App\Models\RegistroRir;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AvaliacaoConsolidadaExport implements FromArray, WithStyles, WithTitle, WithColumnWidths
{
    protected string|int $ano;
    protected array $mesesData = [];

    private const MESES_NOMES = [
        '01' => 'JANEIRO',
        '02' => 'FEVEREIRO',
        '03' => 'MARÇO',
        '04' => 'ABRIL',
        '05' => 'MAIO',
        '06' => 'JUNHO',
        '07' => 'JULHO',
        '08' => 'AGOSTO',
        '09' => 'SETEMBRO',
        '10' => 'OUTUBRO',
        '11' => 'NOVEMBRO',
        '12' => 'DEZEMBRO',
    ];

    public function __construct(string|int $ano)
    {
        $this->ano = $ano;
        $this->processarDados();
    }

    public function title(): string
    {
        return 'Avaliação ' . $this->ano;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 14,
            'C' => 12,
            'D' => 14,
            'E' => 2,
            'F' => 22,
            'G' => 14,
            'H' => 12,
            'I' => 14,
        ];
    }

    private function processarDados(): void
    {
        $registros = RegistroRir::with('fornecedor')->get();

        $agrupado = [];

        foreach ($registros as $registro) {
            $mes = $registro->mes_referencia;
            $fornecedor = $registro->fornecedor->nome ?? 'N/A';
            $classificacao = $registro->classificacao ?? 'Insatisfatório';

            if (!isset($agrupado[$mes])) {
                $agrupado[$mes] = [];
            }
            if (!isset($agrupado[$mes][$fornecedor])) {
                $agrupado[$mes][$fornecedor] = [
                    'otimo' => 0,
                    'bom' => 0,
                    'regular' => 0,
                ];
            }

            switch ($classificacao) {
                case 'Ótimo':
                    $agrupado[$mes][$fornecedor]['otimo']++;
                    break;
                case 'Bom':
                    $agrupado[$mes][$fornecedor]['bom']++;
                    break;
                case 'Regular':
                case 'Insatisfatório':
                default:
                    $agrupado[$mes][$fornecedor]['regular']++;
                    break;
            }
        }

        ksort($agrupado);
        $this->mesesData = $agrupado;
    }

    public function array(): array
    {
        $rows = [];
        $meses = array_keys($this->mesesData);

        for ($i = 0; $i < count($meses); $i += 2) {
            $mes1 = $meses[$i] ?? null;
            $mes2 = $meses[$i + 1] ?? null;

            $bloco = $this->gerarBlocoDoisMeses($mes1, $mes2);
            foreach ($bloco as $linha) {
                $rows[] = $linha;
            }

            $rows[] = [];
        }

        return $rows;
    }

    private function gerarBlocoDoisMeses(?string $mes1, ?string $mes2): array
    {
        $linhas = [];

        $header1 = $mes1 ? $this->getNomeMes($mes1) : '';
        $header2 = $mes2 ? $this->getNomeMes($mes2) : '';

        $linhas[] = [
            $header1,
            '',
            '',
            '',
            '',
            $header2,
            '',
            '',
            '',
        ];

        $linhas[] = [
            $mes1 ? 'FORNECEDOR' : '',
            $mes1 ? 'ÓTIMO 90 A 100' : '',
            $mes1 ? 'BOM 70 A 90' : '',
            $mes1 ? 'REGULAR 50 A 70' : '',
            '',
            $mes2 ? 'FORNECEDOR' : '',
            $mes2 ? 'ÓTIMO 90 A 100' : '',
            $mes2 ? 'BOM 70 A 90' : '',
            $mes2 ? 'REGULAR 50 A 70' : '',
        ];

        $fornecedores1 = $mes1 ? array_keys($this->mesesData[$mes1] ?? []) : [];
        $fornecedores2 = $mes2 ? array_keys($this->mesesData[$mes2] ?? []) : [];

        sort($fornecedores1);
        sort($fornecedores2);

        $maxRows = max(count($fornecedores1), count($fornecedores2), 1);

        for ($r = 0; $r < $maxRows; $r++) {
            $f1 = $fornecedores1[$r] ?? '';
            $f2 = $fornecedores2[$r] ?? '';

            $d1 = $f1 ? ($this->mesesData[$mes1][$f1] ?? null) : null;
            $d2 = $f2 ? ($this->mesesData[$mes2][$f2] ?? null) : null;

            $linhas[] = [
                $f1,
                $d1 ? ($d1['otimo'] ?: '') : '',
                $d1 ? ($d1['bom'] ?: '') : '',
                $d1 ? ($d1['regular'] ?: '') : '',
                '',
                $f2,
                $d2 ? ($d2['otimo'] ?: '') : '',
                $d2 ? ($d2['bom'] ?: '') : '',
                $d2 ? ($d2['regular'] ?: '') : '',
            ];
        }

        return $linhas;
    }

    private function getNomeMes(string $mesRef): string
    {
        $parts = explode('-', $mesRef);
        $mesNum = $parts[1] ?? '01';
        return self::MESES_NOMES[$mesNum] ?? $mesRef;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $greenFill = [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFB4E5A2']
        ];

        $centerAlign = ['horizontal' => Alignment::HORIZONTAL_CENTER];

        $thinBorder = [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ];

        for ($row = 1; $row <= $highestRow; $row++) {
            $cellA = $sheet->getCell("A{$row}")->getValue();
            $cellF = $sheet->getCell("F{$row}")->getValue();
            $cellB = $sheet->getCell("B{$row}")->getValue();

            // Detecta cabeçalhos principais (ex: JANEIRO)
            $isMesHeader = in_array($cellA, array_values(self::MESES_NOMES)) || in_array($cellF, array_values(self::MESES_NOMES));

            // Detecta subcabeçalhos (ex: FORNECEDOR)
            $isSubHeader = ($cellA === 'FORNECEDOR' || $cellF === 'FORNECEDOR');

            if ($isMesHeader) {
                // Mescla e aplica estlo ao Título do Mês
                if (!empty($cellA))
                    $sheet->mergeCells("A{$row}:D{$row}");
                if (!empty($cellF))
                    $sheet->mergeCells("F{$row}:I{$row}");

                $style = [
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => $greenFill,
                    'alignment' => $centerAlign,
                    'borders' => $thinBorder,
                ];

                if (!empty($cellA))
                    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($style);
                if (!empty($cellF))
                    $sheet->getStyle("F{$row}:I{$row}")->applyFromArray($style);
            }

            if ($isSubHeader) {
                // Aplica estilo à linha de colunas (FORNECEDOR, ÓTIMO...)
                $style = [
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => $greenFill,
                    'alignment' => $centerAlign,
                    'borders' => $thinBorder,
                ];

                if ($cellA === 'FORNECEDOR')
                    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($style);
                if ($cellF === 'FORNECEDOR')
                    $sheet->getStyle("F{$row}:I{$row}")->applyFromArray($style);
            }

            if (!$isMesHeader && !$isSubHeader && !empty($cellA)) {
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                    'borders' => $thinBorder,
                ]);
                $sheet->getStyle("B{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            if (!$isMesHeader && !$isSubHeader && !empty($cellF)) {
                $sheet->getStyle("F{$row}:I{$row}")->applyFromArray([
                    'borders' => $thinBorder,
                ]);
                $sheet->getStyle("G{$row}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        return [];
    }
}
