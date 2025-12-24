<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AnalyzeController;

Route::get('/', function () {
    return Inertia::render('TTSAnalyzer/index');
})->name('TTSAnalyzer');

Route::post('/analyze', [AnalyzeController::class, 'create'])->name('analyze.data');
Route::get('/analyze', [AnalyzeController::class, 'output'])->name('analyze.result');

Route::prefix('compensation')->name('compensation.')->group(function () {
    Route::get('/tts', function () {
        return Inertia::render('Compensation/tts');
    })->name('tts');
    Route::get('/adf', function () {
        return Inertia::render('Compensation/adf');
    })->name('adf');
    Route::get('/outage', function () {
        return Inertia::render('Compensation/outage');
    })->name('outage');
});
Route::get('/omhelper', function () {
    return Inertia::render('OMhelper/index');
})->name('OMhelper');

Route::get('/office', function () {
    return Inertia::render('office/index');
})->name('office');



Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
