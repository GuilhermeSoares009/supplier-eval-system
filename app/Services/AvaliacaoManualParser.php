<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class AvaliacaoManualParser
{
    private const MESES_MAP = [
        'JANEIRO' => '01',
        'FEVEREIRO' => '02',
        'MARCO' => '03',
        'ABRIL' => '04',
        'MAIO' => '05',
        'JUNHO' => '06',
        'JULHO' => '07',
        'AGOSTO' => '08',
        'SETEMBRO' => '09',
        'OUTUBRO' => '10',
        'NOVEMBRO' => '11',
        'DEZEMBRO' => '12',
    ];

    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $data = [];
        $index = [];
        $rowIndex = [];

        $mesEsquerda = null;
        $mesDireita = null;

        for ($row = 1; $row <= $highestRow; $row++) {
            $cellA = $this->getCellString($sheet, $row, 1);
            $cellF = $this->getCellString($sheet, $row, 6);

            $mesA = $this->normalizarMes($cellA);
            $mesF = $this->normalizarMes($cellF);

            if ($mesA) {
                $mesEsquerda = $mesA;
            }
            if ($mesF) {
                $mesDireita = $mesF;
            }

            if ($mesA || $mesF) {
                continue;
            }

            if ($this->isSubHeader($cellA) || $this->isSubHeader($cellF)) {
                continue;
            }

            if ($mesEsquerda && $cellA !== '') {
                $this->appendFornecedor(
                    $data,
                    $index,
                    $rowIndex,
                    $mesEsquerda,
                    $cellA,
                    $this->parseCount($sheet, $row, 2),
                    $this->parseCount($sheet, $row, 3),
                    $this->parseCount($sheet, $row, 4)
                );
            }

            if ($mesDireita && $cellF !== '') {
                $this->appendFornecedor(
                    $data,
                    $index,
                    $rowIndex,
                    $mesDireita,
                    $cellF,
                    $this->parseCount($sheet, $row, 7),
                    $this->parseCount($sheet, $row, 8),
                    $this->parseCount($sheet, $row, 9)
                );
            }
        }

        return [
            'data' => $data,
            'index' => $index,
        ];
    }

    private function getCellString($sheet, int $row, int $col): string
    {
        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
        if ($value === null) {
            return '';
        }
        return trim((string) $value);
    }

    private function parseCount($sheet, int $row, int $col): int
    {
        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
        return is_numeric($value) ? (int) $value : 0;
    }

    private function appendFornecedor(
        array &$data,
        array &$index,
        array &$rowIndex,
        string $mes,
        string $fornecedor,
        int $otimo,
        int $bom,
        int $regular
    ): void {
        if (!isset($data[$mes])) {
            $data[$mes] = [];
            $index[$mes] = [];
            $rowIndex[$mes] = [];
        }

        if (!isset($rowIndex[$mes][$fornecedor])) {
            $data[$mes][] = [
                'fornecedor' => $fornecedor,
                'otimo' => $otimo,
                'bom' => $bom,
                'regular' => $regular,
            ];
            $rowIndex[$mes][$fornecedor] = count($data[$mes]) - 1;
        } else {
            $pos = $rowIndex[$mes][$fornecedor];
            $data[$mes][$pos]['otimo'] += $otimo;
            $data[$mes][$pos]['bom'] += $bom;
            $data[$mes][$pos]['regular'] += $regular;
        }

        if (!isset($index[$mes][$fornecedor])) {
            $index[$mes][$fornecedor] = [
                'otimo' => 0,
                'bom' => 0,
                'regular' => 0,
            ];
        }
        $index[$mes][$fornecedor]['otimo'] += $otimo;
        $index[$mes][$fornecedor]['bom'] += $bom;
        $index[$mes][$fornecedor]['regular'] += $regular;
    }

    private function isSubHeader(string $value): bool
    {
        $token = $this->normalizeToken($value);
        if ($token === '') {
            return false;
        }

        $subHeaders = [
            'FORNECEDOR',
            'OTIMO',
            'OTIMO 90 A 100',
            'BOM',
            'BOM 70 A 90',
            'REGULAR',
            'REGULAR 50 A 70',
        ];

        foreach ($subHeaders as $header) {
            if ($token === $header) {
                return true;
            }
        }

        return false;
    }

    private function normalizarMes(string $value): ?string
    {
        $token = $this->normalizeToken($value);
        if ($token === '') {
            return null;
        }

        foreach (self::MESES_MAP as $mesNome => $mesNum) {
            if ($token === $mesNome || str_contains($token, $mesNome)) {
                return $mesNum;
            }
        }

        return null;
    }

    private function normalizeToken(string $value): string
    {
        $token = trim(mb_strtoupper($value));
        if ($token === '') {
            return '';
        }

        $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $token);
        if ($trans !== false && $trans !== null) {
            $token = $trans;
        }

        $token = preg_replace('/\s+/', ' ', $token);
        return $token ?? '';
    }
}
