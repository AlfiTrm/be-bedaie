<?php

use App\Http\Controllers\SalesPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/sales-pages', [SalesPageController::class, 'index']);
    Route::post('/sales-pages', [SalesPageController::class, 'store']);
    Route::get('/sales-pages/{id}', [SalesPageController::class, 'show']);
    Route::delete('/sales-pages/{id}', [SalesPageController::class, 'destroy']);
});
