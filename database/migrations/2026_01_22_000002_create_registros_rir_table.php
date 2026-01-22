<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registros_rir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->cascadeOnDelete();
            $table->float('nota_total');
            $table->string('classificacao');
            $table->date('data_recebimento');
            $table->string('mes_referencia')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_rir');
    }
};
