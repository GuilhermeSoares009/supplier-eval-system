<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('registros_rir', function (Blueprint $table) {
            $table->string('numero_pedido')->nullable()->after('fornecedor_id');
            $table->string('numero_nota_fiscal')->nullable()->after('numero_pedido');
            $table->unsignedInteger('total_itens_pedido')->nullable()->after('numero_nota_fiscal');
            $table->unsignedInteger('itens_atendidos_nota')->nullable()->after('total_itens_pedido');
            $table->float('acuracidade')->nullable()->after('itens_atendidos_nota');
            $table->unsignedTinyInteger('criterio_embalagem')->nullable()->after('acuracidade');
            $table->unsignedTinyInteger('criterio_temperatura')->nullable()->after('criterio_embalagem');
            $table->unsignedTinyInteger('criterio_prazo')->nullable()->after('criterio_temperatura');
            $table->unsignedTinyInteger('criterio_validade')->nullable()->after('criterio_prazo');
            $table->unsignedTinyInteger('criterio_atendimento')->nullable()->after('criterio_validade');
            $table->float('total_pontos')->nullable()->after('criterio_atendimento');
        });
    }

    public function down(): void
    {
        Schema::table('registros_rir', function (Blueprint $table) {
            $table->dropColumn([
                'numero_pedido',
                'numero_nota_fiscal',
                'total_itens_pedido',
                'itens_atendidos_nota',
                'acuracidade',
                'criterio_embalagem',
                'criterio_temperatura',
                'criterio_prazo',
                'criterio_validade',
                'criterio_atendimento',
                'total_pontos',
            ]);
        });
    }
};
