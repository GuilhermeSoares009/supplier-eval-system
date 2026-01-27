<?php

namespace Database\Seeders;

use App\Models\Fornecedor;
use App\Models\FornecedorAlias;
use Illuminate\Database\Seeder;

class FornecedorAliasSeeder extends Seeder
{
    public function run(): void
    {
        // Exemplo: SIEMENS HEALTHCARE -> SIEMENS
        $aliases = [
            'SIEMENS HEALTHCARE' => 'SIEMENS',
            'SIEMENS LTDA' => 'SIEMENS',
            'LABORATORIO ROCHE' => 'ROCHE',
        ];

        foreach ($aliases as $alias => $nomeOficial) {
            $fornecedor = Fornecedor::firstOrCreate(['nome' => $nomeOficial]);

            FornecedorAlias::firstOrCreate(
                ['alias' => $alias],
                ['fornecedor_id' => $fornecedor->id]
            );
        }
    }
}
