<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Rotas para o Chat de Desenvolvimento Local
Route::get('/chat', [WebChatController::class, 'show'])->name('chat.show');
Route::post('/chat', [WebChatController::class, 'store'])->name('chat.store');
Route::post('/chat/clear', [WebChatController::class, 'clear'])->name('chat.clear');
