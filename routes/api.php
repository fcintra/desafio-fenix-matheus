<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::get('exams', [ExamController::class, 'index']);
    Route::post('exams', [ExamController::class, 'store']);
    Route::get('exams/{exam}', [ExamController::class, 'show']);
    Route::post('exams/{exam}/submit', [ExamController::class, 'submit'])->name('exams.submit');
    Route::get('exams/{exam}/ranking', [ExamController::class, 'ranking'])->name('exams.ranking');
    Route::get('ranking', [ExamController::class, 'globalRanking'])->name('ranking.global');
});
