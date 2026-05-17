<?php

use App\Http\Controllers\ChatController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/chat');

Route::get('/chat', [ChatController::class, 'index'])->name('chat');
Route::post('/chat/stream', [ChatController::class, 'stream'])->name('chat.stream');

// Stub "login": qualquer redirect pra route('login') (ex.: Passport quando
// ninguém está autenticado) cai aqui, faz login no primeiro user e
// segue pra URL original. Demo only.
Route::get('/login', function () {
    Auth::login(User::firstOrFail());

    return redirect()->intended('/');
})->name('login');
