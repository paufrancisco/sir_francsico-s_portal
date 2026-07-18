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
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\Admin\AdminFaqController;
use App\Http\Controllers\GradeCorrectionController;

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

    Route::get('chat', [AdminChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{student}', [AdminChatController::class, 'show'])->name('chat.show');
    Route::post('chat/{student}/reply', [AdminChatController::class, 'reply'])->name('chat.reply');

    Route::get('faqs', [AdminFaqController::class, 'index'])->name('faqs.index');
    Route::post('faqs', [AdminFaqController::class, 'store'])->name('faqs.store');
    Route::put('faqs/{faq}', [AdminFaqController::class, 'update'])->name('faqs.update');
    Route::delete('faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('faqs.destroy');

    // Admin-facing (dapat naka-loob sa auth middleware group mo, katulad ng /paulo/* routes mo)
    Route::get('/paulo/grade-corrections', [GradeCorrectionController::class, 'index'])->name('grade-corrections.index');
    Route::patch('/paulo/grade-corrections/{gradeCorrection}/resolve', [GradeCorrectionController::class, 'resolve'])->name('grade-corrections.resolve');
});

Route::post('/portal/chat/verify', [ChatController::class, 'verify'])->middleware('throttle:6,1');
Route::post('/portal/chat/send', [ChatController::class, 'send'])->middleware('throttle:20,1');
Route::get('/portal/chat/history', [ChatController::class, 'history'])->middleware('throttle:30,1');

Route::post('/portal/grades/verify', [StudentDashboardController::class, 'verifyGrades'])
    ->middleware('throttle:6,1') // 6 attempts per minute, konting brute-force protection
    ->name('portal.grades.verify');

Route::get('/', [StudentDashboardController::class, 'index']);

Route::get('/dashboard', function () {
    return redirect('/paulo');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
// Student-facing (public, verified via student_number+password sa request body)
Route::post('/portal/grades/correction', [GradeCorrectionController::class, 'store']);



require __DIR__.'/auth.php';