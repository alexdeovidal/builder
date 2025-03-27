<?php

use App\Http\Controllers\Api\BuilderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpenAIController;

Route::middleware('auth:sanctum')->post('/chat', OpenAIController::class);
Route::post('/builder', [BuilderController::class, 'generate']);
Route::get('/builder/projects', [BuilderController::class, 'listProjects']);
Route::post('/projects/{name}/save', [BuilderController::class, 'save']);
Route::get('/projects/{name}/zip', [BuilderController::class, 'downloadZip']);
Route::get('/projects/{name}', [BuilderController::class, 'load']);
Route::get('/projects', [BuilderController::class, 'index']);
