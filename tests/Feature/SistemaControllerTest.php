<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\RegistroRir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SistemaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_limpa_os_dados_do_sistema(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Fornecedor A']);

        RegistroRir::create([
            'fornecedor_id' => $fornecedor->id,
            'numero_pedido' => '123',
            'numero_nota_fiscal' => '456',
            'total_itens_pedido' => 10,
            'itens_atendidos_nota' => 10,
            'acuracidade' => 1,
            'criterio_embalagem' => 1,
            'criterio_temperatura' => 1,
            'criterio_prazo' => 1,
            'criterio_validade' => 1,
            'criterio_atendimento' => 1,
            'total_pontos' => 1,
            'nota_total' => 1,
            'classificacao' => 'Ótimo',
            'data_recebimento' => '2026-01-23',
            'mes_referencia' => '2026-01',
        ]);

        $response = $this->postJson('/api/limpar-dados');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
        $this->assertEquals(0, RegistroRir::count());
        $this->assertEquals(0, Fornecedor::count());
    }
}
