<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\RegistroRir;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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

                    $data = $this->parseDate($linha['data_recebimento']);
                    if (!$data) {
                        $ignorados++;
                        continue;
                    }

                    $totalItens = $this->normalizarInteiro($linha['total_itens_pedido'] ?? null);
                    $itensAtendidos = $this->normalizarInteiro($linha['itens_atendidos_nota'] ?? null);
                    if ($totalItens === null || $totalItens === 0 || $itensAtendidos === null) {
                        $ignorados++;
                        continue;
                    }

                    $embalagem = $this->normalizarBinario($linha['criterio_embalagem'] ?? null);
                    $temperatura = $this->normalizarBinario($linha['criterio_temperatura'] ?? null);
                    $prazo = $this->normalizarBinario($linha['criterio_prazo'] ?? null);
                    $validade = $this->normalizarBinario($linha['criterio_validade'] ?? null);
                    $atendimento = $this->normalizarBinario($linha['criterio_atendimento'] ?? null);

                    if ($embalagem === null || $temperatura === null || $prazo === null || $validade === null || $atendimento === null) {
                        $ignorados++;
                        continue;
                    }

                    $fornecedor = Fornecedor::firstOrCreate([
                        'nome' => trim($linha['fornecedor']),
                    ]);

                    $acuracidade = $this->calcularAcuracidade($itensAtendidos, $totalItens);
                    $totalPontos = $this->calcularTotalPontos($acuracidade, $embalagem, $temperatura, $prazo, $validade, $atendimento);
                    $classificacao = $this->classificar($totalPontos);

                    RegistroRir::create([
                        'fornecedor_id' => $fornecedor->id,
                        'numero_pedido' => $linha['numero_pedido'],
                        'numero_nota_fiscal' => $linha['numero_nota_fiscal'],
                        'total_itens_pedido' => $totalItens,
                        'itens_atendidos_nota' => $itensAtendidos,
                        'acuracidade' => $acuracidade,
                        'criterio_embalagem' => $embalagem,
                        'criterio_temperatura' => $temperatura,
                        'criterio_prazo' => $prazo,
                        'criterio_validade' => $validade,
                        'criterio_atendimento' => $atendimento,
                        'total_pontos' => $totalPontos,
                        'nota_total' => $totalPontos,
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
            $numeroPedido = $this->cellValue($sheet, $row, $columnMap['numero_pedido'] ?? null);
            $numeroNota = $this->cellValue($sheet, $row, $columnMap['numero_nota_fiscal'] ?? null);
            $totalItens = $this->cellValue($sheet, $row, $columnMap['total_itens_pedido'] ?? null);
            $itensAtendidos = $this->cellValue($sheet, $row, $columnMap['itens_atendidos_nota'] ?? null);
            $embalagem = $this->cellValue($sheet, $row, $columnMap['criterio_embalagem'] ?? null);
            $temperatura = $this->cellValue($sheet, $row, $columnMap['criterio_temperatura'] ?? null);
            $prazo = $this->cellValue($sheet, $row, $columnMap['criterio_prazo'] ?? null);
            $validade = $this->cellValue($sheet, $row, $columnMap['criterio_validade'] ?? null);
            $atendimento = $this->cellValue($sheet, $row, $columnMap['criterio_atendimento'] ?? null);

            if ($fornecedor === null && $data === null && $numeroPedido === null) {
                continue;
            }

            $rows[] = [
                'fornecedor' => $fornecedor,
                'data_recebimento' => $data,
                'numero_pedido' => $numeroPedido,
                'numero_nota_fiscal' => $numeroNota,
                'total_itens_pedido' => $totalItens,
                'itens_atendidos_nota' => $itensAtendidos,
                'criterio_embalagem' => $embalagem,
                'criterio_temperatura' => $temperatura,
                'criterio_prazo' => $prazo,
                'criterio_validade' => $validade,
                'criterio_atendimento' => $atendimento,
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
            if ($this->isHeaderMatch($value, ['nº do pedido', 'n° do pedido', 'numero do pedido'])) {
                $map['numero_pedido'] = $col;
            }
            if ($this->isHeaderMatch($value, ['nº nota fiscal', 'n° nota fiscal', 'numero nota fiscal', 'nº da nota fiscal', 'n° da nota fiscal'])) {
                $map['numero_nota_fiscal'] = $col;
            }
            if ($this->isHeaderMatch($value, ['total de itens do pedido', 'total itens do pedido'])) {
                $map['total_itens_pedido'] = $col;
            }
            if ($this->isHeaderMatch($value, ['itens atendidos na nota', 'itens atendidos'])) {
                $map['itens_atendidos_nota'] = $col;
            }
            if ($value === 'embalagem') {
                $map['criterio_embalagem'] = $col;
            }
            if ($value === 'temperatura') {
                $map['criterio_temperatura'] = $col;
            }
            if ($this->isHeaderMatch($value, ['prazo de entrega', 'prazo'])) {
                $map['criterio_prazo'] = $col;
            }
            if ($value === 'validade') {
                $map['criterio_validade'] = $col;
            }
            if ($this->isHeaderMatch($value, ['atendimento da transportadora', 'atendimento transportadora', 'atendimento'])) {
                $map['criterio_atendimento'] = $col;
            }
        }

        $required = [
            'data_recebimento',
            'fornecedor',
            'numero_pedido',
            'numero_nota_fiscal',
            'total_itens_pedido',
            'itens_atendidos_nota',
            'criterio_embalagem',
            'criterio_temperatura',
            'criterio_prazo',
            'criterio_validade',
            'criterio_atendimento',
        ];
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

    private function normalizarInteiro(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = preg_replace('/[^0-9]/', '', $value);
        }

        return $value === '' ? null : (int) $value;
    }

    private function normalizarBinario(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', $value));
        }

        $numero = (float) $value;

        if (in_array($numero, [0.0, 1.0], true)) {
            return (int) $numero;
        }

        return $numero >= 1 ? 1 : 0;
    }

    private function calcularAcuracidade(int $itensAtendidos, int $totalItens): float
    {
        if ($totalItens <= 0) {
            return 0.0;
        }

        $valor = $itensAtendidos / $totalItens;
        return max(0, min(1, $valor));
    }

    private function calcularTotalPontos(
        float $acuracidade,
        int $embalagem,
        int $temperatura,
        int $prazo,
        int $validade,
        int $atendimento
    ): float {
        $criterios = [$acuracidade, $embalagem, $temperatura, $prazo, $validade, $atendimento];
        $total = array_sum($criterios) / count($criterios);
        return max(0, min(1, $total));
    }

    private function isHeaderMatch(string $value, array $candidates): bool
    {
        return in_array($value, $candidates, true);
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
