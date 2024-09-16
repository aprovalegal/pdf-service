<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\PdfWatermarkController;
use Illuminate\Support\Facades\Route;

Route::post('auth', LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('pdf-watermark/store', [PdfWatermarkController::class, 'store']);
});
