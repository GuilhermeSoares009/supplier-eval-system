<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\RegistroRir;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use RuntimeException;

class RirImportService
{
    public function import(array $files): array
    {
        $importados = 0;
        $ignorados = 0;
        $meses = [];

        DB::transaction(function () use ($files, &$importados, &$ignorados, &$meses) {
            foreach ($files as $file) {
                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $linhas = $this->parseSpreadsheet($file->getRealPath());

                foreach ($linhas as $linha) {
                    if (empty($linha['fornecedor']) || empty($linha['data_recebimento'])) {
                        $ignorados++;
                        continue;
                    }

                    $nota = $this->normalizarNota($linha['nota_total'] ?? null);
                    if ($nota === null) {
                        $ignorados++;
                        continue;
                    }

                    $data = $this->parseDate($linha['data_recebimento']);
                    if (!$data) {
                        $ignorados++;
                        continue;
                    }

                    $fornecedor = Fornecedor::firstOrCreate([
                        'nome' => trim($linha['fornecedor']),
                    ]);

                    $classificacao = $this->classificar($nota);

                    RegistroRir::create([
                        'fornecedor_id' => $fornecedor->id,
                        'nota_total' => $nota,
                        'classificacao' => $classificacao,
                        'data_recebimento' => $data->format('Y-m-d'),
                        'mes_referencia' => $data->format('Y-m'),
                    ]);

                    $meses[] = $data->format('Y-m');
                    $importados++;
                }
            }
        });

        return [
            'importados' => $importados,
            'ignorados' => $ignorados,
            'meses' => array_values(array_unique($meses)),
        ];
    }

    private function parseSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        $headerRow = $this->findHeaderRow($sheet, $highestRow, $highestColumnIndex);
        if (!$headerRow) {
            throw new RuntimeException('Cabeçalho não encontrado no arquivo RIR.');
        }

        $columnMap = $this->buildColumnMap($sheet, $headerRow, $highestColumnIndex);

        $rows = [];
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $fornecedor = $this->cellValue($sheet, $row, $columnMap['fornecedor'] ?? null);
            $data = $this->cellValue($sheet, $row, $columnMap['data_recebimento'] ?? null);
            $nota = $this->cellValue($sheet, $row, $columnMap['nota_total'] ?? null);
            $obs = $this->cellValue($sheet, $row, $columnMap['classificacao'] ?? null);

            if ($fornecedor === null && $data === null && $nota === null) {
                continue;
            }

            $rows[] = [
                'fornecedor' => $fornecedor,
                'data_recebimento' => $data,
                'nota_total' => $nota,
                'classificacao' => $obs,
            ];
        }

        return $rows;
    }

    private function findHeaderRow(Worksheet $sheet, int $highestRow, int $highestColumnIndex): ?int
    {
        $maxSearch = min($highestRow, 15);
        for ($row = 1; $row <= $maxSearch; $row++) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $value = $this->normalizeHeader($this->cellValue($sheet, $row, $col));
                if ($value === 'data de recebimento') {
                    return $row;
                }
            }
        }

        return null;
    }

    private function buildColumnMap(Worksheet $sheet, int $headerRow, int $highestColumnIndex): array
    {
        $map = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $this->normalizeHeader($this->cellValue($sheet, $headerRow, $col));
            if ($value === 'data de recebimento') {
                $map['data_recebimento'] = $col;
            }
            if ($value === 'fornecedor') {
                $map['fornecedor'] = $col;
            }
            if ($value === 'total pontos') {
                $map['nota_total'] = $col;
            }
            if ($value === 'obs.' || $value === 'obs') {
                $map['classificacao'] = $col;
            }
        }

        $required = ['data_recebimento', 'fornecedor', 'nota_total', 'classificacao'];
        foreach ($required as $key) {
            if (!isset($map[$key])) {
                throw new RuntimeException('Colunas obrigatórias não encontradas no arquivo RIR.');
            }
        }

        return $map;
    }

    private function cellValue(Worksheet $sheet, int $row, ?int $column): mixed
    {
        if ($column === null) {
            return null;
        }

        $value = $sheet->getCellByColumnAndRow($column, $row)->getValue();
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }

    private function normalizeHeader(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = str_replace(['"', "'"], '', $normalized);

        return $normalized;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value));
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable $exception) {
                return null;
            }
        }

        return null;
    }

    private function normalizarNota(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', $value));
        }

        $nota = (float) $value;
        if ($nota > 1) {
            $nota = $nota / 100;
        }

        $nota = max(0, min(1, $nota));

        return $nota;
    }

    private function classificar(float $nota): string
    {
        if ($nota >= 0.9) {
            return 'Ótimo';
        }

        if ($nota >= 0.7) {
            return 'Bom';
        }

        if ($nota >= 0.5) {
            return 'Regular';
        }

        return 'Insatisfatório';
    }
}
