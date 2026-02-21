<?php

use App\Http\Controllers\Api\AudioController;
use App\Http\Controllers\Api\PromotionController;
use Illuminate\Support\Facades\Route;

Route::prefix('audios')->name('audios.')->group(function (): void {
    Route::get('/', [AudioController::class, 'index'])->name('index');
    Route::get('/active', [AudioController::class, 'active'])->name('active');
    Route::get('/{audio}/schedule', [AudioController::class, 'schedule'])->name('schedule');
});

Route::prefix('promotions')->name('promotions.')->group(function (): void {
    Route::post('/', [PromotionController::class, 'store'])->name('store');
    Route::post('/preview', [PromotionController::class, 'preview'])->name('preview');
    Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
    Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
});
