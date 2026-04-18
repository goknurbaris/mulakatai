<?php

use App\Http\Controllers\InterviewSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InterviewSessionController::class, 'start'])->name('interviews.start');
Route::post('/interviews', [InterviewSessionController::class, 'store'])->name('interviews.store');
Route::get('/interviews/{interviewSession}', [InterviewSessionController::class, 'show'])->name('interviews.show');
Route::post('/interviews/{interviewSession}/answers', [InterviewSessionController::class, 'submitAnswer'])->name('interviews.answer');
Route::get('/interviews/{interviewSession}/result', [InterviewSessionController::class, 'result'])->name('interviews.result');
