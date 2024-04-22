<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\PdfWatermarkController;
use Illuminate\Support\Facades\Route;

Route::post('auth', LoginController::class);

Route::post('pdf-watermark', PdfWatermarkController::class)
     ->middleware('auth:sanctum');
