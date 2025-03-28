<?php

use App\Http\Controllers\Api\BuilderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpenAIController;


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chat', OpenAIController::class);
    Route::post('/builder', [BuilderController::class, 'generate']);
    Route::get('/builder/projects', [BuilderController::class, 'listProjects']);
    Route::post('/builder/{project}/extend', [BuilderController::class, 'extend']);
    Route::post('/projects/{name}/save', [BuilderController::class, 'save']);
    Route::get('/projects/{name}/zip', [BuilderController::class, 'downloadZip']);
    Route::get('/projects/{name}', [BuilderController::class, 'load']);
    Route::get('/projects', [BuilderController::class, 'index']);
    Route::post('/projects/{name}/deploy', [BuilderController::class, 'deployToGitHub']);
    Route::get('/projects/{project}/messages', [BuilderController::class, 'messages']);
    Route::post('/projects/{project}/chat', [BuilderController::class, 'chat']);

    Route::get('/chat/{project}', [BuilderController::class, 'chatHistory']);
    Route::post('/chat/{project}', [BuilderController::class, 'chatSend']);
});


