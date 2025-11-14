<?php

use App\Http\Controllers\PersonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rotas para gerenciar pessoas
Route::get('/persons', [PersonController::class, 'index']);
Route::get('/persons/sample', [PersonController::class, 'sample']);
Route::get('/persons/clear', [PersonController::class, 'clear']);
Route::post('/persons', [PersonController::class, 'store']);
