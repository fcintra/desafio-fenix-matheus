<?php

use App\Http\Controllers\ExamController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::post('exams/{exam}/submit', [ExamController::class, 'submit'])->name('exams.submit');
    Route::get('exams/{exam}/ranking', [ExamController::class, 'ranking'])->name('exams.ranking');
    Route::get('ranking', [ExamController::class, 'globalRanking'])->name('ranking.global');
});
