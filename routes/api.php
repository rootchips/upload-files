<?php
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ProductController;

Route::get('/uploads', [UploadController::class, 'index']);
Route::post('/uploads', [UploadController::class, 'store']);
Route::get('/uploads/{id}/progress', [UploadController::class, 'progress']);

Route::get('/products', [ProductController::class, 'index']);