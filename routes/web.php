<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Главная страница резюме (существующий проект)
Route::get('/', [\App\Http\Controllers\IndexController::class, 'index'])->name('home');

// Маршруты для новостной ленты
Route::prefix('news')->name('news.')->group(function () {
    Route::get('/', [NewsController::class, 'index'])->name('index');
    Route::get('/rubrika/{slug}', [NewsController::class, 'rubrika'])->name('rubrika');
    Route::get('/article/{id}', [NewsController::class, 'article'])->name('article');
});
