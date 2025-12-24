<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AnalyzeController;

Route::get('/', function () {
    return Inertia::render('TTSAnalyzer/index');
})->name('TTSAnalyzer');

Route::post('/analyze', [AnalyzeController::class, 'create'])->name('analyze.data');
Route::get('/analyze', [AnalyzeController::class, 'output'])->name('analyze.result');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
