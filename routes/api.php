<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportacaoController;
use App\Http\Controllers\ImportacaoController;
use App\Http\Controllers\SistemaController;
use Illuminate\Support\Facades\Route;

Route::post('/importar-rir', [ImportacaoController::class, 'importar']);
Route::get('/dashboard-mensal', [DashboardController::class, 'mensal']);

Route::get('/exportar-avaliacao', [ExportacaoController::class, 'exportar']);
Route::post('/limpar-dados', [SistemaController::class, 'limpar']);
