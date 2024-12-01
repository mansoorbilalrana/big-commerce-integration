<?php

use App\Http\Middleware\JsonMiddleware;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\BigCommerceController;

Route::prefix('bigcommerce')->middleware(['api'])->group(function () {
    // Get all orders with optional query parameters
    Route::get('/orders', [BigCommerceController::class, 'getOrders']);

    // Create a new order
    Route::post('/orders', [BigCommerceController::class, 'createOrder']);

    // Update an existing order
    Route::put('/orders', [BigCommerceController::class, 'updateOrder']);

    // Get all products
    Route::get('/products', [BigCommerceController::class, 'getProducts']);

    // Update an existing product
    Route::put('/products', [BigCommerceController::class, 'updateProduct']);

    // Get a specific product by URL
    Route::get('/product', [BigCommerceController::class, 'getProduct']);

    // Create a new webhook
    Route::post('/webhook', [BigCommerceController::class, 'createWebhook']);


    Route::post('/order-callback', [BigCommerceController::class, 'orderCallback']);
});

