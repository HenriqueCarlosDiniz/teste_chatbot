<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiChatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Rota para receber as mensagens da interface Streamlit
Route::post('/chat', [ApiChatController::class, 'handle']);