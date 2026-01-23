<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\RegistroRir;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
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

        Log::channel('rir_import')->debug('Linha de cabeçalho detectada', [
            'header_row' => $headerRow,
            'highest_row' => $highestRow,
            'highest_column_index' => $highestColumnIndex,
        ]);

        $columnMap = $this->buildColumnMap($sheet, $headerRow, $highestColumnIndex);

        Log::channel('rir_import')->debug('Mapa de colunas detectado', [
            'header_row' => $headerRow,
            'map' => $columnMap,
        ]);

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
        $maxSearch = min($highestRow, 30);
        $bestRow = null;
        $bestScore = 0;

        for ($row = 1; $row <= $maxSearch; $row++) {
            $score = 0;

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $header = $this->normalizeHeader($this->cellValue($sheet, $row, $col));
                if ($header === '') {
                    continue;
                }

                if ($this->isDataRecebimentoHeader($header)) {
                    $score += 2;
                }
                if ($this->isFornecedorHeader($header)) {
                    $score += 2;
                }
                if ($this->isNumeroPedidoHeader($header)) {
                    $score += 1;
                }
                if ($this->isNumeroNotaFiscalHeader($header)) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = $row;
            }
        }

        return $bestScore >= 4 ? $bestRow : null;
    }

    private function buildColumnMap(Worksheet $sheet, int $headerRow, int $highestColumnIndex): array
    {
        $map = [];
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $this->normalizeHeader($this->cellValue($sheet, $headerRow, $col));
            if ($value === '') {
                continue;
            }

            $headers[$col] = $value;

            if ($this->isDataRecebimentoHeader($value)) {
                $map['data_recebimento'] = $col;
            }
            if ($this->isFornecedorHeader($value)) {
                $map['fornecedor'] = $col;
            }
            if ($this->isNumeroPedidoHeader($value)) {
                $map['numero_pedido'] = $col;
            }
            if ($this->isNumeroNotaFiscalHeader($value)) {
                $map['numero_nota_fiscal'] = $col;
            }
            if ($this->hasAllWords($value, ['total', 'itens', 'pedido'])) {
                $map['total_itens_pedido'] = $col;
            }
            if ($this->isItensAtendidosHeader($value)) {
                $map['itens_atendidos_nota'] = $col;
            }
            if ($this->hasWord($value, 'embalagem')) {
                $map['criterio_embalagem'] = $col;
            }
            if ($this->hasWord($value, 'temperatura')) {
                $map['criterio_temperatura'] = $col;
            }
            if ($this->hasWord($value, 'prazo')) {
                $map['criterio_prazo'] = $col;
            }
            if ($this->hasWord($value, 'validade')) {
                $map['criterio_validade'] = $col;
            }
            if ($this->hasWord($value, 'atendimento')) {
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
                $faltantes = array_values(array_filter($required, fn ($k) => !isset($map[$k])));

                Log::channel('rir_import')->warning('Falha ao mapear colunas obrigatórias', [
                    'header_row' => $headerRow,
                    'faltantes' => $faltantes,
                    'headers_normalizados' => $headers,
                    'map_detectado' => $map,
                ]);

                throw new RuntimeException('Colunas obrigatórias não encontradas no arquivo RIR: ' . implode(', ', $faltantes) . '.');
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
        $normalized = str_replace(["\r", "\n", "\t"], ' ', $normalized);
        $normalized = str_replace(['º', '°'], 'o', $normalized);

        $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if (is_string($translit) && $translit !== '') {
            $normalized = $translit;
        }

        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
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

    private function words(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s+/', $value) ?: [];
        $parts = array_map(function ($part) {
            $part = trim((string) $part);
            if ($part === '') {
                return '';
            }

            $part = preg_replace('/\d+/', '', $part);

            return trim((string) $part);
        }, $parts);

        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

        return array_values(array_unique($parts));
    }

    private function hasWord(string $header, string $word): bool
    {
        return in_array($word, $this->words($header), true);
    }

    private function hasAllWords(string $header, array $words): bool
    {
        $headerWords = $this->words($header);
        foreach ($words as $word) {
            if (!in_array($word, $headerWords, true)) {
                return false;
            }
        }
        return true;
    }

    private function isDataRecebimentoHeader(string $header): bool
    {
        return $this->hasAllWords($header, ['data', 'recebimento'])
            || $this->hasAllWords($header, ['data', 'receb']);
    }

    private function isFornecedorHeader(string $header): bool
    {
        return $this->hasWord($header, 'fornecedor');
    }

    private function isNumeroPedidoHeader(string $header): bool
    {
        $words = $this->words($header);
        if (!in_array('pedido', $words, true)) {
            return false;
        }

        return in_array('numero', $words, true)
            || in_array('n', $words, true)
            || in_array('no', $words, true)
            || in_array('nro', $words, true)
            || in_array('num', $words, true);
    }

    private function isNumeroNotaFiscalHeader(string $header): bool
    {
        $words = $this->words($header);

        if (in_array('nf', $words, true) || in_array('nfe', $words, true)) {
            return true;
        }

        if (!(in_array('nota', $words, true) && in_array('fiscal', $words, true))) {
            return false;
        }

        return in_array('numero', $words, true)
            || in_array('n', $words, true)
            || in_array('no', $words, true)
            || in_array('nro', $words, true)
            || in_array('num', $words, true)
            || $this->hasAllWords($header, ['da', 'nota', 'fiscal']);
    }

    private function isItensAtendidosHeader(string $header): bool
    {
        if (!$this->hasWord($header, 'itens')) {
            return false;
        }

        $words = $this->words($header);
        $temAtendidos = in_array('atendidos', $words, true) || in_array('entregues', $words, true);
        $temNota = in_array('nota', $words, true) || in_array('nf', $words, true);

        return $temAtendidos && $temNota;
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
