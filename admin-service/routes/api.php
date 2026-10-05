<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
| Admin JSON API, served under /admin/api. Authenticated with the same JWT the NestJS API issues
| (Bearer header or the shared access_token cookie), so one token works for both services.
*/
Route::middleware(['auth:jwt', 'admin'])->group(function () {
    Route::get('me', [UserController::class, 'me']);
    Route::get('users', [UserController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show'])->whereNumber('id');
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
});
