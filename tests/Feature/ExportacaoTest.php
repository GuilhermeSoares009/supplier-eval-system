<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\RegistroRir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporta_arquivo_xlsx_vazio_quando_nao_ha_dados(): void
    {
        $response = $this->getJson('/api/exportar-avaliacao?ano=2026');

        $response->assertOk(); // Espera 200, não 422
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_exporta_arquivo_xlsx_com_dados(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor Teste']);
        RegistroRir::create([
            'fornecedor_id' => $fornecedor->id,
            'numero_pedido' => '123',
            'numero_nota_fiscal' => '456',
            'data_recebimento' => '2026-01-15',
            'mes_referencia' => '2026-01',
            'total_itens_pedido' => 10,
            'itens_atendidos_nota' => 10,
            'acuracidade' => 1.0,
            'classificacao' => 'Ótimo',
            'total_pontos' => 1.0,
            'nota_total' => 1.0, // Campo obrigatório adicionado
            # Campos obrigatórios adicionais
            'criterio_embalagem' => 1,
            'criterio_temperatura' => 1,
            'criterio_prazo' => 1,
            'criterio_validade' => 1,
            'criterio_atendimento' => 1,
        ]);

        $response = $this->get('/api/exportar-avaliacao?ano=2026');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Verifica se o filename está correto (pode vir encoded ou as-is dependendo do Laravel/Browser)
        // O Laravel geralmente faz fallback para ASCII se não usar filename*, mas vamos testar contains simples
        $this->assertStringContainsString('AVALIAÇÃO DE FORNECEDORES.xlsx', urldecode($response->headers->get('content-disposition')));
    }
}
