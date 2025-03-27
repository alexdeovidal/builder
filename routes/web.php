<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::get('/chat', function () {
    return Inertia::render('chat/index');
})->middleware(['auth', 'verified'])->name('chat');

Route::get('/builder', function () {
    return Inertia::render('builder/index');
})->middleware(['auth', 'verified'])->name('builder');



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
