<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InterviewSessionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');
Route::view('/features', 'features')->name('features');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [InterviewSessionController::class, 'history'])->name('interviews.history');
    Route::get('/app', [InterviewSessionController::class, 'start'])->name('interviews.start');
    Route::post('/interviews', [InterviewSessionController::class, 'store'])->name('interviews.store');
    Route::get('/interviews/{interviewSession}/resume', [InterviewSessionController::class, 'resume'])->name('interviews.resume');
    Route::get('/interviews/{interviewSession}', [InterviewSessionController::class, 'show'])->name('interviews.show');
    Route::post('/interviews/{interviewSession}/answers', [InterviewSessionController::class, 'submitAnswer'])->name('interviews.answer');
    Route::get('/interviews/{interviewSession}/result', [InterviewSessionController::class, 'result'])->name('interviews.result');
});
