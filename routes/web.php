<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Product Page
|--------------------------------------------------------------------------
*/

Route::get(
    '/products',
    [ProductController::class, 'index']
);

/*
|--------------------------------------------------------------------------
| Product List
|--------------------------------------------------------------------------
|
| Search
| Sorting
| Pagination
| Price Filter
| Stock Filter
| Statistics
|
*/

Route::get(
    '/products/list',
    [ProductController::class, 'fetch']
);

Route::get(
    '/products/suggestions',
    [ProductController::class, 'suggestions']
);

/*
|--------------------------------------------------------------------------
| Product CRUD
|--------------------------------------------------------------------------
*/

Route::post(
    '/products/store',
    [ProductController::class, 'store']
);

Route::post(
    '/products/update/{id}',
    [ProductController::class, 'update']
);

Route::delete(
    '/products/delete/{id}',
    [ProductController::class, 'destroy']
);

Route::get(
    '/products/trash',
    [ProductController::class, 'trash']
);

Route::post(
    '/products/restore/{id}',
    [ProductController::class, 'restore']
);

/*
|--------------------------------------------------------------------------
| Bulk Delete
|--------------------------------------------------------------------------
*/

Route::post(
    '/products/bulk-delete',
    [ProductController::class, 'bulkDelete']
);

/*
|--------------------------------------------------------------------------
| Import
|--------------------------------------------------------------------------
*/

Route::post(
    '/products/import',
    [ProductController::class, 'import']
);

/*
|--------------------------------------------------------------------------
| Export
|--------------------------------------------------------------------------
|
| Supports:
| - Search
| - Price filters
| - Stock filter
| - Sorting
|
*/

Route::get(
    '/products/export',
    [ProductController::class, 'export']
);


/*
|--------------------------------------------------------------------------
| Import / Export History
|--------------------------------------------------------------------------
|
| Supports:
| - Search
| - Operation filter
| - Status filter
|
*/

Route::get(
    '/products/history',
    [ProductController::class, 'history']
);