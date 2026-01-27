<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FornecedorAlias extends Model
{
    protected $table = 'fornecedor_aliases';

    protected $fillable = [
        'alias',
        'fornecedor_id',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
