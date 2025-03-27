<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpenAIController;

Route::middleware('auth:sanctum')->post('/chat', OpenAIController::class);
