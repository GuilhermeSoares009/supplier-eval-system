<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\FornecedorAlias;
use App\Models\RegistroRir;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class RirImportService
{
    // Configurações do Layout Fixo
    // O arquivo real tem 4 linhas de cabeçalho irrelevante/logo. A linha 5 contém os dados.
    // Então pulamos 4 linhas.
    private const ROW_OFFSET = 4;
    private const COL_DATA = 1;         // A
    private const COL_FORNECEDOR = 2;   // B
    private const COL_PEDIDO = 3;       // C
    private const COL_NOTA = 4;         // D
    private const COL_TOTAL_ITENS = 5;  // E
    private const COL_ITENS_ATEND = 6;  // F
    // COL G (7) é calculada/ignorada
    private const COL_EMBALAGEM = 8;    // H
    private const COL_TEMPERATURA = 9;  // I
    private const COL_PRAZO = 10;       // J
    private const COL_VALIDADE = 11;    // K
    private const COL_ATENDIMENTO = 12; // L
    private const COL_PONTOS = 13;      // M
    private const COL_OBS = 14;         // N

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

                // Parseia todas as abas do arquivo
                $linhas = $this->parseSpreadsheet($file);

                foreach ($linhas as $linha) {
                    // Validação básica: Fornecedor e Pontos
                    if (empty($linha['fornecedor'])) {
                        $ignorados++;
                        continue;
                    }

                    // Se Data continua inválida mesmo após tentativas de fallback
                    if (!$linha['data_recebimento'] instanceof Carbon) {
                        $ignorados++;
                        continue;
                    }

                    // Normalização
                    $nomeFornecedor = $this->normalizarFornecedor($linha['fornecedor']);
                    $fornecedor = Fornecedor::firstOrCreate(['nome' => $nomeFornecedor]);

                    // Dados para chave única
                    $chaveUnica = [
                        'fornecedor_id' => $fornecedor->id,
                        'numero_pedido' => $linha['numero_pedido'],
                        'numero_nota_fiscal' => $linha['numero_nota_fiscal'],
                        'data_recebimento' => $linha['data_recebimento']->format('Y-m-d'),
                    ];

                    $dadosUpdate = [
                        'total_itens_pedido' => $this->normalizarInteiro($linha['total_itens_pedido']),
                        'itens_atendidos_nota' => $this->normalizarInteiro($linha['itens_atendidos_nota']),
                        'criterio_embalagem' => $this->normalizarBinario($linha['criterio_embalagem']),
                        'criterio_temperatura' => $this->normalizarBinario($linha['criterio_temperatura']),
                        'criterio_prazo' => $this->normalizarBinario($linha['criterio_prazo']),
                        'criterio_validade' => $this->normalizarBinario($linha['criterio_validade']),
                        'criterio_atendimento' => $this->normalizarBinario($linha['criterio_atendimento']),
                        'total_pontos' => $linha['total_pontos'],
                        'classificacao' => $linha['classificacao'], // Usa a classificação do arquivo ou recalcula? O USER disse "Se Coluna M for erro...". "Embora o sistema leia o valor pronto, a validação deve ser...".
                        // Vou recalcular para garantir consistência, mas o usuário disse para ler a coluna M.
                        // O requisito diz: "Fórmula Reversa: O sistema deve ler a string 100% ou 0.83 e converter para float".
                        'nota_total' => $linha['total_pontos'],
                        'mes_referencia' => $linha['data_recebimento']->format('Y-m'),
                    ];

                    // Recalcular acuracidade se possível
                    $dadosUpdate['acuracidade'] = $this->calcularAcuracidade(
                        $dadosUpdate['itens_atendidos_nota'],
                        $dadosUpdate['total_itens_pedido']
                    );

                    // Se pontos vieram zerados ou inválidos, podemos tentar recalcular,
                    // mas a prioridade é o valor do arquivo (normalizado).

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
            'atualizados' => $atualizados, // Novo campo
            'ignorados' => $ignorados,
            'meses' => array_values(array_unique($meses)),
        ];
    }

    private function parseSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $allRows = [];

        // Itera sobre todas as abas (JAN, FEV, MAR...)
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();
            $highestRow = $sheet->getHighestRow();

            // Layout fixo: começa na linha 7 (ROW_OFFSET + 1)
            for ($row = self::ROW_OFFSET + 1; $row <= $highestRow; $row++) {

                $fornecedorRaw = $this->getVal($sheet, $row, self::COL_FORNECEDOR);
                $pontosRaw = $this->getVal($sheet, $row, self::COL_PONTOS);

                // Regra: "Se Coluna B (Fornecedor) estiver vazia OU Coluna M (Pontos) for erro/#DIV/0!, a linha deve ser descartada"
                if (empty($fornecedorRaw) || $this->isErroExcel($pontosRaw)) {
                    continue;
                }

                $dataRaw = $this->getVal($sheet, $row, self::COL_DATA);

                // Passa o nome da ABA como contexto para data
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
        $val = $sheet->getCell([$col, $row])->getValue(); # Usando array [col, row] para coordenadas 1-based (A=1)
        // Correção: getCellByColumnAndRow usa (col, row). getCell usa 'A1'.
        // Melhor usar getCellByColumnAndRow($col, $row)->getValue();
        return $sheet->getCellByColumnAndRow($col, $row)->getValue();
    }

    private function carregarAliases(): void
    {
        // Se a tabela não existir (migração pendente), evita crash
        try {
            $this->aliases = FornecedorAlias::pluck('fornecedor_id', 'alias')->toArray();
            // Ops, preciso do nome, não ID, para firstOrCreate.
            // Melhor carregar relacional ou apenas array de nomes normais.
            // Para simplificar: carrega [alias => nome_oficial]
            // Mas o FornecedorAlias tem FK para Fornecedor.
            // Então carrego: alias -> fornecedor->nome
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
        if (!$raw)
            return '';
        $normalized = mb_strtoupper(trim($raw));
        return $this->aliases[$normalized] ?? $normalized;
    }

    private function isErroExcel(mixed $val): bool
    {
        if (is_string($val)) {
            // Verifica #DIV/0!, #N/A, #REF!, etc.
            if (str_starts_with($val, '#'))
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
                // Se tinha % ou se é um valor inteiro alto (ex: 83, 100), divide por 100
                // Se é um decimal pequeno (ex: 0.95) e não tinha %, assume que já está em decimal
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
        // Prioridade 1: Data da célula
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

        // Prioridade 2: Nome do arquivo (ex: "FOR.GER.008 RIR.RIR - JAN 25.csv") ou nome da aba (ex: "JAN")
        $dateFromName = $this->extrairDataDoNome($filename, $sheetName);
        if ($dateFromName) {
            return $dateFromName;
        }

        return null; // Fallback se tudo falhar
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

        // 1. Tenta extrair Mês E Ano do nome da ABA (ex: "MAR 25", "Janeiro 2026")
        if (preg_match('/(JAN|FEV|MAR|ABR|MAI|JUN|JUL|AGO|SET|OUT|NOV|DEZ)[^0-9]*(\d{2,4})/i', $sheetName, $matches)) {
            $mesStr = mb_strtoupper(substr($matches[1], 0, 3));
            $ano = (int) $matches[2];
            if ($ano < 100)
                $ano += 2000;
            return Carbon::create($ano, $meses[$mesStr], 1);
        }

        // 2. Tenta extrair do nome da aba (apenas Mês) + Ano do Arquivo
        // Pega as 3 primeiras letras e converte para maiúsculo
        $sheetNamePrefix = mb_strtoupper(mb_substr(trim($sheetName), 0, 3));
        if (array_key_exists($sheetNamePrefix, $meses)) {
            // Se a aba é um mês, precisamos do ano do nome do arquivo
            if (preg_match('/(\d{4})/', $filename, $matches)) {
                $ano = (int) $matches[1];
                return Carbon::create($ano, $meses[$sheetNamePrefix], 1);
            }
            // Se não encontrar ano no arquivo, tenta do ano atual (menos ideal)
            // return Carbon::create(Carbon::now()->year, $meses[$sheetNameUpper], 1);
        }

        // 3. Tenta extrair do nome do arquivo (ex: "RIR JAN 25.xlsx")
        // Procura padrões como "JAN 25", "FEV 2025", "01-2025" no nome do arquivo
        // Regex para "MMM YY" ou "MMM YYYY"
        if (preg_match('/(JAN|FEV|MAR|ABR|MAI|JUN|JUL|AGO|SET|OUT|NOV|DEZ)[\s\-_]*(\d{2,4})/i', $filename, $matches)) {
            $mesStr = mb_strtoupper($matches[1]);
            $ano = (int) $matches[2];
            if ($ano < 100)
                $ano += 2000; // 25 -> 2025

            return Carbon::create($ano, $meses[$mesStr], 1);
        }
        return null;
    }



    private function normalizarInteiro(mixed $val): int
    {
        if (is_numeric($val))
            return (int) $val;
        return 0;
    }

    private function normalizarBinario(mixed $val): int
    {
        if (is_numeric($val))
            return (int) $val;
        return 0;
    }

    private function calcularAcuracidade($atendidos, $total): float
    {
        if ($total > 0)
            return $atendidos / $total;
        return 0.0;
    }
}
