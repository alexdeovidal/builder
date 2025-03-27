<?php

use App\Http\Controllers\Api\BuilderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpenAIController;

Route::middleware('auth:sanctum')->post('/chat', OpenAIController::class);
Route::post('/builder', [BuilderController::class, 'generate']);
Route::get('/builder/projects', [BuilderController::class, 'listProjects']);

