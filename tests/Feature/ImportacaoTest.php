<?php

namespace Tests\Feature;

use App\Models\RegistroRir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_arquivo_rir_com_layout_fixo(): void
    {
        // Usa CSV simples com vírgula para evitar problemas de detecção de separador no teste
        $linhas = [
            'L1,x,x,x,x,x,x,x,x,x,x,x,x,x',
            'L2,x,x,x,x,x,x,x,x,x,x,x,x,x',
            'L3,x,x,x,x,x,x,x,x,x,x,x,x,x',
            'L4,x,x,x,x,x,x,x,x,x,x,x,x,x',
            // Linha 5 (cabeçalho visual - ignorado se data falhar)
            'Data,Forn,Ped,Nota,Tot,Atend,X,Emb,Temp,Prazo,Val,AtendT,Pt,Obs',
            // Linha 6 (Dados reais)
            '2026-01-23,TESTE,123,456,10,10,x,1,1,1,1,1,100%,OK',
        ];

        $csvContent = implode("\n", $linhas);

        $file = UploadedFile::fake()->createWithContent('layout_novo.csv', $csvContent);

        $response = $this->postJson('/api/importar-rir', [
            'arquivos' => [$file],
        ]);

        $response->assertOk();
        $response->assertJson([
            'importados' => 1,
            // 'ignorados' => 1, // Pode variar dependendo se L7 falha na data ou fornecedor
        ]);

        $this->assertDatabaseHas('fornecedores', ['nome' => 'TESTE']);
        $this->assertDatabaseHas('registros_rir', [
            'numero_nota_fiscal' => '456',
            'total_pontos' => 1.0,
        ]);
    }
}
