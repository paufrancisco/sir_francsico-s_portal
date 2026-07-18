<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\GradeImportController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\DashboardController;

Route::middleware(['auth'])->prefix('paulo')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('sections', SectionController::class);

    Route::get('sections/{section}/students/import', [StudentImportController::class, 'create'])->name('sections.students.import');
    Route::post('sections/{section}/students/import', [StudentImportController::class, 'store'])->name('sections.students.import.store');
    Route::post('sections/{section}/students/reveal', [SectionController::class, 'revealStudents'])->name('sections.students.reveal');

    Route::get('grades/import', [GradeImportController::class, 'create'])->name('grades.import');
    Route::post('sections/{section}/grades/import', [GradeImportController::class, 'store'])->name('sections.grades.import.store');

    Route::resource('announcements', AnnouncementController::class)->except(['show']);

    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::post('students/reveal', [StudentController::class, 'reveal'])->name('students.reveal');
});

Route::get('/', [StudentDashboardController::class, 'index']);

Route::get('/dashboard', function () {
    return redirect('/paulo');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';