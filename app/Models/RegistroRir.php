<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroRir extends Model
{
    protected $table = 'registros_rir';

    protected $fillable = [
        'fornecedor_id',
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
        'nota_total',
        'classificacao',
        'data_recebimento',
        'mes_referencia',
    ];

    protected $casts = [
        'data_recebimento' => 'date',
        'nota_total' => 'float',
        'acuracidade' => 'float',
        'total_pontos' => 'float',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
