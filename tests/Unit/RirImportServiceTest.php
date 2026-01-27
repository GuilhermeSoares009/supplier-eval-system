<?php

namespace Tests\Unit;

use App\Services\RirImportService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RirImportServiceTest extends TestCase
{
    private RirImportService $service;
    private $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RirImportService();
        $this->reflection = new ReflectionClass($this->service);
    }

    private function invokeMethod(string $method, ...$args)
    {
        $method = $this->reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invoke($this->service, ...$args);
    }

    public function test_extrair_data_do_nome(): void
    {
        // Cenários: [filename, sheetName, expectedDate]
        $cenarios = [
            // C1: Data no arquivo
            ['RIR - JAN 25.csv', 'Planilha1', '2025-01-01'],
            // C2: Data na aba (Mês) + Ano no arquivo
            ['RIR 2026.xlsx', 'FEV', '2026-02-01'],
            ['RIR_2025_FINAL.xlsx', 'Janeiro', '2025-01-01'],
            // C3: Data completa na aba (se suportado? A lógica atual suporta apenas Mês na aba e busca Ano no arquivo)
            // Se a aba for "JAN 25", a lógica de extraçãoGenérica (fallback) deve pegar.
            ['Relatorio.xlsx', 'MAR 25', '2025-03-01'],
            // C4: Sem data
            ['Arquivo Sem Data.csv', 'Sheet1', null],
        ];

        foreach ($cenarios as $idx => [$file, $sheet, $expected]) {
            $result = $this->invokeMethod('extrairDataDoNome', $file, $sheet);
            if ($expected === null) {
                $this->assertNull($result, "Falha no cenário $idx: Esperado null");
            } else {
                $this->assertNotNull($result, "Falha no cenário $idx: Esperado data, retornou null");
                $this->assertEquals($expected, $result->format('Y-m-d'), "Falha no cenário $idx");
            }
        }
    }

    public function test_normalizar_pontuacao(): void
    {
        $cenarios = [
            ['100%', 1.0],
            ['83%', 0.83],
            ['0,95', 0.95],
            ['0.50', 0.50],
            [1, 1.0],
            [0.7, 0.7],
            ['Texto', 0.0],
        ];

        foreach ($cenarios as [$input, $expected]) {
            $result = $this->invokeMethod('normalizarPontuacao', $input);
            $this->assertEqualsWithDelta($expected, $result, 0.001, "Falha para input: " . var_export($input, true));
        }
    }

    public function test_normalizar_inteiro(): void
    {
        $this->assertEquals(10, $this->invokeMethod('normalizarInteiro', '10'));
        $this->assertEquals(5, $this->invokeMethod('normalizarInteiro', 5));
        $this->assertEquals(0, $this->invokeMethod('normalizarInteiro', 'abc'));
        $this->assertEquals(0, $this->invokeMethod('normalizarInteiro', null));
    }
}
