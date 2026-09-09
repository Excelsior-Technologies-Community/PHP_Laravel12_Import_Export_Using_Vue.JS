<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// Load product page
Route::get('/products', [ProductController::class, 'index']);

// Fetch product list
Route::get('/products/list', [ProductController::class, 'fetch']);

// Store new product
Route::post('/products/store', [ProductController::class, 'store']);

// Update existing product
Route::post('/products/update/{id}', [ProductController::class, 'update']);

// Delete product
Route::delete('/products/delete/{id}', [ProductController::class, 'destroy']);

// Import products from Excel
Route::post('/products/import', [ProductController::class, 'import']);

// Export products to Excel
Route::get('/products/export', [ProductController::class, 'export']);

// Import / Export history
Route::get('/products/history', [ProductController::class, 'history']);