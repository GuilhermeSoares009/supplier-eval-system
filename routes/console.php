<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sistema:limpar', function () {
    DB::statement('DELETE FROM registros_rir');
    DB::statement('DELETE FROM fornecedores');
    DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('registros_rir', 'fornecedores')");

    $this->info('Base SQLite limpa com sucesso.');
})->purpose('Resetar o SQLite e limpar dados de fornecedores.');
