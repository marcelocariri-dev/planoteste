<?php

use Illuminate\Support\Facades\Route;
use Src\Interfaces\Http\Controllers\CasoTesteController;
use Src\Interfaces\Http\Controllers\ExecucaoController;
use Src\Interfaces\Http\Controllers\PlanoTesteController;

Route::get('/planos', [PlanoTesteController::class, 'index']);
Route::post('/planos', [PlanoTesteController::class, 'store']);
Route::get('/planos/{id}', [PlanoTesteController::class, 'show'])->whereNumber('id');
Route::put('/planos/{id}', [PlanoTesteController::class, 'update'])->whereNumber('id');
Route::delete('/planos/{id}', [PlanoTesteController::class, 'destroy'])->whereNumber('id');
Route::post('/planos/{id}/concluir', [PlanoTesteController::class, 'concluir'])->whereNumber('id');
Route::post('/planos/{id}/reabrir', [PlanoTesteController::class, 'reabrir'])->whereNumber('id');

Route::post('/planos/{planoId}/casos', [CasoTesteController::class, 'store'])->whereNumber('planoId');
Route::put('/casos/{id}', [CasoTesteController::class, 'update'])->whereNumber('id');
Route::delete('/casos/{id}', [CasoTesteController::class, 'destroy'])->whereNumber('id');

Route::get('/casos/{casoId}/execucoes', [ExecucaoController::class, 'index'])->whereNumber('casoId');
Route::post('/casos/{casoId}/execucoes', [ExecucaoController::class, 'store'])->whereNumber('casoId');
