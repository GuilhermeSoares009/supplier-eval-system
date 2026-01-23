<?php

namespace Tests\Feature;

use App\Models\RegistroRir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_arquivo_rir_e_calcula_acuracidade_e_total(): void
    {
        $csv = implode("\n", [
                'Data de recebimento,Fornecedor,Nº do pedido,Nº Nota Fiscal,Total de itens do pedido,Itens atendidos na nota,Embalagem,Temperatura,Prazo de Entrega,Validade,Atendimento da transportadora',
                '2026-01-23,Fornecedor A,123*1,456,10,5,1,1,1,1,1',
        ]);

        $file = UploadedFile::fake()->createWithContent('rir.csv', $csv);

        $response = $this->postJson('/api/importar-rir', [
            'arquivos' => [$file],
        ]);

        $response->assertOk();
        $response->assertJson([
            'importados' => 1,
        ]);

        $registro = RegistroRir::first();
        $this->assertNotNull($registro);

        $totalEsperado = (0.5 + 1 + 1 + 1 + 1 + 1) / 6;

        $this->assertEqualsWithDelta(0.5, $registro->acuracidade, 0.001);
        $this->assertEqualsWithDelta($totalEsperado, $registro->total_pontos, 0.01);
        $this->assertEquals('Ótimo', $registro->classificacao);
    }
}
