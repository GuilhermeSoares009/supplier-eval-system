<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\FornecedorAlias;
use App\Models\RegistroRir;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class RirImportService
{
    private const ROW_OFFSET = 4;
    private const COL_DATA = 1;
    private const COL_FORNECEDOR = 2;
    private const COL_PEDIDO = 3;
    private const COL_NOTA = 4;
    private const COL_TOTAL_ITENS = 5;
    private const COL_ITENS_ATEND = 6;
    private const COL_EMBALAGEM = 8;
    private const COL_TEMPERATURA = 9;
    private const COL_PRAZO = 10;
    private const COL_VALIDADE = 11;
    private const COL_ATENDIMENTO = 12;
    private const COL_PONTOS = 13;
    private const COL_OBS = 14;

    private array $aliases = [];

    public function import(array $files): array
    {
        $this->carregarAliases();
        $importados = 0;
        $ignorados = 0;
        $atualizados = 0;
        $meses = [];

        DB::transaction(function () use ($files, &$importados, &$ignorados, &$atualizados, &$meses) {
            foreach ($files as $file) {
                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $linhas = $this->parseSpreadsheet($file);

                foreach ($linhas as $linha) {
                    if (empty($linha['fornecedor'])) {
                        $ignorados++;
                        continue;
                    }

                    if (!$linha['data_recebimento'] instanceof Carbon) {
                        $ignorados++;
                        continue;
                    }

                    $nomeFornecedor = $this->normalizarFornecedor($linha['fornecedor']);
                    $fornecedor = Fornecedor::firstOrCreate(['nome' => $nomeFornecedor]);

                    $chaveUnica = [
                        'fornecedor_id' => $fornecedor->id,
                        'numero_pedido' => $linha['numero_pedido'],
                        'numero_nota_fiscal' => $linha['numero_nota_fiscal'],
                        'data_recebimento' => $linha['data_recebimento']->format('Y-m-d'),
                    ];

                    $pontosFinal = $linha['total_pontos'] > 0
                        ? $linha['total_pontos']
                        : $this->recalcularPontuacao($linha);

                    $dadosUpdate = [
                        'total_itens_pedido' => $this->normalizarInteiro($linha['total_itens_pedido']),
                        'itens_atendidos_nota' => $this->normalizarInteiro($linha['itens_atendidos_nota']),
                        'criterio_embalagem' => $this->normalizarBinario($linha['criterio_embalagem']),
                        'criterio_temperatura' => $this->normalizarBinario($linha['criterio_temperatura']),
                        'criterio_prazo' => $this->normalizarBinario($linha['criterio_prazo']),
                        'criterio_validade' => $this->normalizarBinario($linha['criterio_validade']),
                        'criterio_atendimento' => $this->normalizarBinario($linha['criterio_atendimento']),
                        'total_pontos' => $pontosFinal,
                        'classificacao' => $this->normalizarClassificacao($linha['classificacao'], $pontosFinal),
                        'nota_total' => $pontosFinal,
                        'mes_referencia' => $linha['data_recebimento']->format('Y-m'),
                    ];

                    $dadosUpdate['acuracidade'] = $this->calcularAcuracidade(
                        $dadosUpdate['itens_atendidos_nota'],
                        $dadosUpdate['total_itens_pedido']
                    );

                    $registro = RegistroRir::updateOrCreate($chaveUnica, $dadosUpdate);

                    if ($registro->wasRecentlyCreated) {
                        $importados++;
                    } else {
                        $atualizados++;
                    }

                    $meses[] = $dadosUpdate['mes_referencia'];
                }
            }
        });

        return [
            'importados' => $importados,
            'atualizados' => $atualizados,
            'ignorados' => $ignorados,
            'meses' => array_values(array_unique($meses)),
        ];
    }

    private function parseSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $allRows = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();
            $highestRow = $sheet->getHighestRow();

            for ($row = self::ROW_OFFSET + 1; $row <= $highestRow; $row++) {
                $fornecedorRaw = $this->getVal($sheet, $row, self::COL_FORNECEDOR);
                $pontosRaw = $this->getVal($sheet, $row, self::COL_PONTOS);

                if (empty($fornecedorRaw) || $this->isErroExcel($pontosRaw)) {
                    continue;
                }

                $dataRaw = $this->getVal($sheet, $row, self::COL_DATA);
                $data = $this->parseDate($dataRaw, $file->getClientOriginalName(), $sheetName);

                $allRows[] = [
                    'data_recebimento' => $data,
                    'fornecedor' => $fornecedorRaw,
                    'numero_pedido' => $this->getVal($sheet, $row, self::COL_PEDIDO),
                    'numero_nota_fiscal' => $this->getVal($sheet, $row, self::COL_NOTA),
                    'total_itens_pedido' => $this->getVal($sheet, $row, self::COL_TOTAL_ITENS),
                    'itens_atendidos_nota' => $this->getVal($sheet, $row, self::COL_ITENS_ATEND),
                    'criterio_embalagem' => $this->getVal($sheet, $row, self::COL_EMBALAGEM),
                    'criterio_temperatura' => $this->getVal($sheet, $row, self::COL_TEMPERATURA),
                    'criterio_prazo' => $this->getVal($sheet, $row, self::COL_PRAZO),
                    'criterio_validade' => $this->getVal($sheet, $row, self::COL_VALIDADE),
                    'criterio_atendimento' => $this->getVal($sheet, $row, self::COL_ATENDIMENTO),
                    'total_pontos' => $this->normalizarPontuacao($pontosRaw),
                    'classificacao' => $this->getVal($sheet, $row, self::COL_OBS),
                ];
            }
        }

        return $allRows;
    }

    private function getVal($sheet, int $row, int $col): mixed
    {
        return $sheet->getCellByColumnAndRow($col, $row)->getValue();
    }

    private function carregarAliases(): void
    {
        try {
            $this->aliases = [];
            $results = FornecedorAlias::with('fornecedor')->get();
            foreach ($results as $item) {
                if ($item->fornecedor) {
                    $this->aliases[$item->alias] = $item->fornecedor->nome;
                }
            }
        } catch (\Throwable $e) {
            $this->aliases = [];
        }
    }

    private function normalizarFornecedor(?string $raw): string
    {
        if (!$raw) {
            return '';
        }

        $normalized = mb_strtoupper(trim($raw));

        $termosIgnorados = [
            'CONFORME',
            'NÃO CONFORME',
            'NAO CONFORME',
            'TOTAL',
            'JANEIRO',
            'FEVEREIRO',
            'MARÇO',
            'MARCO',
            'ABRIL',
            'MAIO',
            'JUNHO',
            'JULHO',
            'AGOSTO',
            'SETEMBRO',
            'OUTUBRO',
            'NOVEMBRO',
            'DEZEMBRO',
            'FORNECEDOR',
            'ÓTIMO',
            'OTIMO',
            'BOM',
            'REGULAR',
            'INSATISFATÓRIO',
            'INSATISFATORIO',
        ];

        if (in_array($normalized, $termosIgnorados)) {
            return '';
        }

        return $this->aliases[$normalized] ?? $normalized;
    }

    private function isErroExcel(mixed $val): bool
    {
        if (is_string($val) && str_starts_with($val, '#')) {
            return true;
        }
        return false;
    }

    private function normalizarPontuacao(mixed $val): float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }

        if (is_string($val)) {
            $isPercent = str_contains($val, '%');
            $v = str_replace(['%', ' '], '', $val);
            $v = str_replace(',', '.', $v);

            if (is_numeric($v)) {
                $floatVal = (float) $v;
                if ($isPercent || $floatVal > 1) {
                    return $floatVal / 100;
                }
                return $floatVal;
            }
        }

        return 0.0;
    }

    private function parseDate(mixed $val, string $filename, string $sheetName): ?Carbon
    {
        if ($val instanceof \DateTimeInterface) {
            return Carbon::instance($val);
        }

        if (is_numeric($val)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($val));
            } catch (\Exception $e) {
            }
        }

        if (is_string($val)) {
            try {
                return Carbon::createFromFormat('d/m/Y', trim($val));
            } catch (\Exception $e) {
                try {
                    return Carbon::parse($val);
                } catch (\Exception $e) {
                }
            }
        }

        $dateFromName = $this->extrairDataDoNome($filename, $sheetName);
        if ($dateFromName) {
            return $dateFromName;
        }

        return null;
    }

    private function extrairDataDoNome(string $filename, string $sheetName): ?Carbon
    {
        $meses = [
            'JAN' => 1,
            'FEV' => 2,
            'MAR' => 3,
            'ABR' => 4,
            'MAI' => 5,
            'JUN' => 6,
            'JUL' => 7,
            'AGO' => 8,
            'SET' => 9,
            'OUT' => 10,
            'NOV' => 11,
            'DEZ' => 12
        ];

        if (preg_match('/(JAN|FEV|MAR|ABR|MAI|JUN|JUL|AGO|SET|OUT|NOV|DEZ)[^0-9]*(\d{2,4})/i', $sheetName, $matches)) {
            $mesStr = mb_strtoupper(substr($matches[1], 0, 3));
            $ano = (int) $matches[2];
            if ($ano < 100) {
                $ano += 2000;
            }
            return Carbon::create($ano, $meses[$mesStr], 1);
        }

        $sheetNamePrefix = mb_strtoupper(mb_substr(trim($sheetName), 0, 3));
        if (array_key_exists($sheetNamePrefix, $meses)) {
            if (preg_match('/(\d{4})/', $filename, $matches)) {
                $ano = (int) $matches[1];
                return Carbon::create($ano, $meses[$sheetNamePrefix], 1);
            }
        }

        if (preg_match('/(JAN|FEV|MAR|ABR|MAI|JUN|JUL|AGO|SET|OUT|NOV|DEZ)[\s\-_]*(\d{2,4})/i', $filename, $matches)) {
            $mesStr = mb_strtoupper($matches[1]);
            $ano = (int) $matches[2];
            if ($ano < 100) {
                $ano += 2000;
            }
            return Carbon::create($ano, $meses[$mesStr], 1);
        }

        return null;
    }

    private function normalizarInteiro(mixed $val): int
    {
        if (is_numeric($val)) {
            return (int) $val;
        }
        return 0;
    }

    private function normalizarBinario(mixed $val): int
    {
        if (is_numeric($val)) {
            return (int) $val;
        }
        return 0;
    }

    private function calcularAcuracidade($atendidos, $total): float
    {
        if ($total > 0) {
            return $atendidos / $total;
        }
        return 0.0;
    }

    private function recalcularPontuacao(array $linha): float
    {
        $acuracidade = $this->calcularAcuracidade(
            $this->normalizarInteiro($linha['itens_atendidos_nota']),
            $this->normalizarInteiro($linha['total_itens_pedido'])
        );

        $soma = $acuracidade
            + $this->normalizarBinario($linha['criterio_embalagem'])
            + $this->normalizarBinario($linha['criterio_temperatura'])
            + $this->normalizarBinario($linha['criterio_prazo'])
            + $this->normalizarBinario($linha['criterio_validade'])
            + $this->normalizarBinario($linha['criterio_atendimento']);

        return $soma / 6;
    }

    private function calcularClassificacao(float $pontos): string
    {
        if ($pontos >= 0.90) {
            return 'Ótimo';
        }
        if ($pontos >= 0.70) {
            return 'Bom';
        }
        if ($pontos >= 0.50) {
            return 'Regular';
        }
        return 'Insatisfatório';
    }

    private function normalizarClassificacao(?string $classificacaoArquivo, float $pontos): string
    {
        if (empty($classificacaoArquivo)) {
            return $this->calcularClassificacao($pontos);
        }

        $normalizada = trim(mb_strtolower($classificacaoArquivo));

        $mapa = [
            'ótimo' => 'Ótimo',
            'otimo' => 'Ótimo',
            'excelente' => 'Ótimo',
            'bom' => 'Bom',
            'regular' => 'Regular',
            'insatisfatório' => 'Insatisfatório',
            'insatisfatorio' => 'Insatisfatório',
            'ruim' => 'Insatisfatório',
            'péssimo' => 'Insatisfatório',
            'pessimo' => 'Insatisfatório',
        ];

        return $mapa[$normalizada] ?? $this->calcularClassificacao($pontos);
    }
}
