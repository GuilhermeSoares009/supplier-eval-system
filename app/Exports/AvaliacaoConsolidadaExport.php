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
        '03' => 'MARÃ‡O',
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

    private const MESES_PARES = [
        ['01', '02'],
        ['03', '04'],
        ['05', '06'],
        ['07', '08'],
        ['09', '10'],
        ['11', '12'],
    ];

    public function __construct(string|int $ano)
    {
        $this->ano = $ano;
        $this->processarDados();
    }

    public function title(): string
    {
        return 'AvaliaÃ§Ã£o ' . $this->ano;
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
        $prefixo = sprintf('%d-', (int) $this->ano);
        $registros = RegistroRir::with('fornecedor')
            ->where('mes_referencia', 'like', $prefixo.'%')
            ->get();

        $agrupado = [];

        foreach ($registros as $registro) {
            $mes = $this->extrairMes($registro->mes_referencia);
            if (!$mes) {
                continue;
            }

            $fornecedor = $registro->fornecedor->nome ?? 'N/A';
            $classificacao = $registro->classificacao ?? 'InsatisfatÃ³rio';

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
                case 'Ã“timo':
                    $agrupado[$mes][$fornecedor]['otimo']++;
                    break;
                case 'Bom':
                    $agrupado[$mes][$fornecedor]['bom']++;
                    break;
                case 'Regular':
                case 'InsatisfatÃ³rio':
                default:
                    $agrupado[$mes][$fornecedor]['regular']++;
                    break;
            }
        }

        ksort($agrupado);
        foreach ($agrupado as $mes => $fornecedores) {
            ksort($fornecedores);
            foreach ($fornecedores as $nome => $dados) {
                $this->mesesData[$mes][] = [
                    'fornecedor' => $nome,
                    'otimo' => $dados['otimo'],
                    'bom' => $dados['bom'],
                    'regular' => $dados['regular'],
                ];
            }
        }
    }

    public function array(): array
    {
        $rows = [];

        foreach (self::MESES_PARES as [$mes1, $mes2]) {
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
            $mes1 ? 'MÃŠS' : '',
            $mes1 ? 'Ã“TIMO 90 A 100' : '',
            $mes1 ? 'BOM 70 A 90' : '',
            $mes1 ? 'REGULAR 50 A 70' : '',
            '',
            $mes2 ? 'MÃŠS' : '',
            $mes2 ? 'Ã“TIMO 90 A 100' : '',
            $mes2 ? 'BOM 70 A 90' : '',
            $mes2 ? 'REGULAR 50 A 70' : '',
        ];

        $dados1 = $mes1 ? ($this->mesesData[$mes1] ?? []) : [];
        $dados2 = $mes2 ? ($this->mesesData[$mes2] ?? []) : [];

        $maxRows = max(count($dados1), count($dados2));

        for ($r = 0; $r < $maxRows; $r++) {
            $linha1 = $dados1[$r] ?? null;
            $linha2 = $dados2[$r] ?? null;

            $linhas[] = [
                $linha1['fornecedor'] ?? '',
                $this->formatarContagem($linha1['otimo'] ?? null),
                $this->formatarContagem($linha1['bom'] ?? null),
                $this->formatarContagem($linha1['regular'] ?? null),
                '',
                $linha2['fornecedor'] ?? '',
                $this->formatarContagem($linha2['otimo'] ?? null),
                $this->formatarContagem($linha2['bom'] ?? null),
                $this->formatarContagem($linha2['regular'] ?? null),
            ];
        }

        return $linhas;
    }

    private function getNomeMes(string $mesNum): string
    {
        return self::MESES_NOMES[$mesNum] ?? $mesNum;
    }

    private function extrairMes(?string $mesRef): ?string
    {
        if (!$mesRef) {
            return null;
        }

        $parts = explode('-', $mesRef);
        $mesNum = $parts[1] ?? null;
        if (!$mesNum || !isset(self::MESES_NOMES[$mesNum])) {
            return null;
        }

        return $mesNum;
    }

    private function formatarContagem(mixed $valor): string|int
    {
        if ($valor === null) {
            return '';
        }

        $intVal = is_numeric($valor) ? (int) $valor : 0;
        return $intVal > 0 ? $intVal : '';
    }

    private function isMesHeader(?string $valor): bool
    {
        if ($valor === null || $valor === '') {
            return false;
        }

        return in_array($valor, array_values(self::MESES_NOMES), true);
    }

    private function isHeaderLinha(?string $valor): bool
    {
        if ($valor === null || $valor === '') {
            return false;
        }

        return $valor === 'MÃŠS';
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

            $isMesHeader = $this->isMesHeader($cellA) || $this->isMesHeader($cellF);
            $isHeaderLinha = $this->isHeaderLinha($cellA) || $this->isHeaderLinha($cellF);

            if ($isMesHeader) {
                if (!empty($cellA)) {
                    $sheet->mergeCells("A{$row}:D{$row}");
                }
                if (!empty($cellF)) {
                    $sheet->mergeCells("F{$row}:I{$row}");
                }

                $style = [
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => $greenFill,
                    'alignment' => $centerAlign,
                    'borders' => $thinBorder,
                ];

                if (!empty($cellA)) {
                    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($style);
                }
                if (!empty($cellF)) {
                    $sheet->getStyle("F{$row}:I{$row}")->applyFromArray($style);
                }
            }

            if ($isHeaderLinha) {
                $style = [
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => $greenFill,
                    'alignment' => $centerAlign,
                    'borders' => $thinBorder,
                ];

                if ($cellA === 'MÃŠS') {
                    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($style);
                }
                if ($cellF === 'MÃŠS') {
                    $sheet->getStyle("F{$row}:I{$row}")->applyFromArray($style);
                }
            }

            if (!$isMesHeader && !$isHeaderLinha && !empty($cellA)) {
                $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                    'borders' => $thinBorder,
                ]);
                $sheet->getStyle("B{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            if (!$isMesHeader && !$isHeaderLinha && !empty($cellF)) {
                $sheet->getStyle("F{$row}:I{$row}")->applyFromArray([
                    'borders' => $thinBorder,
                ]);
                $sheet->getStyle("G{$row}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        return [];
    }
}
