<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroRir extends Model
{
    protected $table = 'registros_rir';

    protected $fillable = [
        'fornecedor_id',
        'nota_total',
        'classificacao',
        'data_recebimento',
        'mes_referencia',
    ];

    protected $casts = [
        'data_recebimento' => 'date',
        'nota_total' => 'float',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
