<?php

use Illuminate\Support\Facades\Route;
use Src\Interfaces\Http\Controllers\BacklogController;
use Src\Interfaces\Http\Controllers\CasoTesteController;
use Src\Interfaces\Http\Controllers\CofreController;
use Src\Interfaces\Http\Controllers\ExecucaoController;
use Src\Interfaces\Http\Controllers\IntegracaoMcpController;
use Src\Interfaces\Http\Controllers\ModuloController;
use Src\Interfaces\Http\Controllers\PlanoTesteController;
use Src\Interfaces\Http\Controllers\RegraNegocioController;


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

// ---------------- Módulos (tag de departamento)

Route::get('/modulos', [ModuloController::class, 'index']);
Route::post('/modulos', [ModuloController::class, 'store']);
Route::put('/modulos/{id}', [ModuloController::class, 'update'])->whereNumber('id');
Route::delete('/modulos/{id}', [ModuloController::class, 'destroy'])->whereNumber('id');

// ---------------- Validação de regras de negócio (PM/PO)
Route::get('/regras', [RegraNegocioController::class, 'index']);
Route::post('/regras', [RegraNegocioController::class, 'store']);
Route::post('/regras/analisar', [RegraNegocioController::class, 'analisar']);
Route::get('/regras/{id}', [RegraNegocioController::class, 'show'])->whereNumber('id');
Route::put('/regras/{id}', [RegraNegocioController::class, 'update'])->whereNumber('id');
Route::delete('/regras/{id}', [RegraNegocioController::class, 'destroy'])->whereNumber('id');
Route::post('/regras/{id}/fluxo/{acao}', [RegraNegocioController::class, 'fluxo'])->whereNumber('id')->whereIn('acao', ['enviar', 'retornar', 'reabrir']);
Route::post('/regras/{id}/parecer', [RegraNegocioController::class, 'parecer'])->whereNumber('id');
Route::get('/regras/{id}/versoes', [RegraNegocioController::class, 'versoes'])->whereNumber('id');
Route::post('/regras/{id}/gerar-plano', [RegraNegocioController::class, 'gerarPlano'])->whereNumber('id');

// ---------------- Cofre de documentos e normas técnicas
Route::get('/cofre/categorias', [CofreController::class, 'categorias']);
Route::post('/cofre/categorias', [CofreController::class, 'salvarCategoria']);
Route::put('/cofre/categorias/{id}', [CofreController::class, 'salvarCategoria'])->whereNumber('id');
Route::delete('/cofre/categorias/{id}', [CofreController::class, 'excluirCategoria'])->whereNumber('id');
Route::get('/cofre/documentos', [CofreController::class, 'index']);
Route::post('/cofre/documentos', [CofreController::class, 'store']);
Route::put('/cofre/documentos/{id}', [CofreController::class, 'update'])->whereNumber('id');
Route::delete('/cofre/documentos/{id}', [CofreController::class, 'destroy'])->whereNumber('id');
Route::get('/cofre/documentos/{id}/download', [CofreController::class, 'download'])->whereNumber('id');

// ---------------- Product backlog
Route::get('/backlog/configuracao', [BacklogController::class, 'configuracao']);
Route::get('/backlog/padroes', [BacklogController::class, 'padroes']);
Route::post('/backlog/padroes', [BacklogController::class, 'salvarPadrao']);
Route::put('/backlog/padroes/{id}', [BacklogController::class, 'salvarPadrao'])->whereNumber('id');
Route::delete('/backlog/padroes/{id}', [BacklogController::class, 'excluirPadrao'])->whereNumber('id');
Route::get('/backlogs', [BacklogController::class, 'index']);
Route::post('/backlogs', [BacklogController::class, 'store']);
Route::get('/backlogs/{id}', [BacklogController::class, 'show'])->whereNumber('id');
Route::delete('/backlogs/{id}', [BacklogController::class, 'destroy'])->whereNumber('id');
Route::post('/backlogs/{id}/resposta', [BacklogController::class, 'importarResposta'])->whereNumber('id');
Route::post('/backlogs/{id}/gerar-plano', [BacklogController::class, 'gerarPlano'])->whereNumber('id');

// ---------------- Integração MCP (casos oficiais da empresa)
Route::get('/mcp/conexoes', [IntegracaoMcpController::class, 'index']);
Route::post('/mcp/conexoes', [IntegracaoMcpController::class, 'store']);
Route::put('/mcp/conexoes/{id}', [IntegracaoMcpController::class, 'update'])->whereNumber('id');
Route::delete('/mcp/conexoes/{id}', [IntegracaoMcpController::class, 'destroy'])->whereNumber('id');
Route::post('/mcp/conexoes/{id}/testar', [IntegracaoMcpController::class, 'testar'])->whereNumber('id');
Route::post('/mcp/conexoes/{id}/casos', [IntegracaoMcpController::class, 'buscarCasos'])->whereNumber('id');
Route::post('/mcp/conexoes/{id}/importar', [IntegracaoMcpController::class, 'importar'])->whereNumber('id');
