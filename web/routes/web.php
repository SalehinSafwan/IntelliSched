<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AcademicTermController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\PdfExportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchedulerInputController;
use App\Http\Controllers\Scheduling\ScheduleGenerationController;
use App\Http\Controllers\Scheduling\ScheduleViewController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherCourseScoreController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\UserController;

// Root redirect to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login',  [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Application Routes
Route::middleware('auth')->group(function () {

    // ── Everyone signed in ───────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Published routines and exams (managers also see drafts).
    Route::get('/schedules', [ScheduleViewController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/{scheduleRunId}', [ScheduleViewController::class, 'show'])->whereNumber('scheduleRunId')->name('schedules.show');
    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->whereNumber('exam')->name('exams.show');

    // PDF exports of routines and exams.
    Route::get('/export/routine/{scheduleRunId}.pdf', [PdfExportController::class, 'routine'])->whereNumber('scheduleRunId')->name('export.routine');
    Route::get('/export/exam/{exam}.pdf', [PdfExportController::class, 'exam'])->whereNumber('exam')->name('export.exam');

    // ── Teacher self-service ─────────────────────────────────────────
    Route::middleware('role:teacher')->prefix('my')->name('my.')->group(function () {
        Route::get('/schedule', [TeacherController::class, 'mySchedule'])->name('schedule');
        Route::get('/availability', [TeacherController::class, 'myAvailability'])->name('availability');
        Route::put('/availability', [TeacherController::class, 'updateMyAvailability'])->name('availability.update');
    });

    // ── Administrators and coordinators ──────────────────────────────
    Route::middleware('role:admin,coordinator')->group(function () {

        // Academic terms
        Route::resource('terms', AcademicTermController::class)->except(['show']);
        Route::post('/terms/{term}/activate', [AcademicTermController::class, 'activate'])->name('terms.activate');

        // Courses
        Route::resource('courses', CourseController::class);

        // Teachers, suitability and availability
        Route::get('/teachers/preferences', [TeacherController::class, 'preferences'])->name('teachers.preferences');
        Route::put('/teachers/{teacher}/suitability', [TeacherController::class, 'updateSuitability'])->name('teachers.suitability');
        Route::get('/teachers/{teacher}/availability', [TeacherController::class, 'availability'])->name('teachers.availability');
        Route::put('/teachers/{teacher}/availability', [TeacherController::class, 'updateAvailability'])->name('teachers.availability.update');
        Route::resource('teachers', TeacherController::class);

        // Rooms and lab eligibility
        Route::resource('rooms', RoomController::class);

        // Batches, sections and course lists
        Route::resource('batches', BatchController::class);
        Route::put('/batches/{batch}/offerings', [BatchController::class, 'updateOfferings'])->name('batches.offerings');
        Route::post('/batches/{batch}/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

        // Time slots
        Route::get('/timeslots', [TimeSlotController::class, 'index'])->name('timeslots.index');
        Route::post('/timeslots', [TimeSlotController::class, 'store'])->name('timeslots.store');
        Route::post('/timeslots/copy', [TimeSlotController::class, 'copyDay'])->name('timeslots.copy');
        Route::put('/timeslots/{timeslot}', [TimeSlotController::class, 'update'])->name('timeslots.update');
        Route::delete('/timeslots/{timeslot}', [TimeSlotController::class, 'destroy'])->name('timeslots.destroy');

        // Routine generation
        Route::get('/schedules/create', [ScheduleGenerationController::class, 'create'])->name('schedules.create');
        Route::post('/schedules/generate', [ScheduleGenerationController::class, 'generate'])->name('schedules.generate');
        Route::get('/schedules/preview/{token}', [ScheduleGenerationController::class, 'preview'])->whereUuid('token')->name('schedules.preview');
        Route::post('/schedules/preview/{token}/approve', [ScheduleGenerationController::class, 'approve'])->whereUuid('token')->name('schedules.approve');

        // Exam schedules
        Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('/exams/{exam}/edit', [ExamController::class, 'edit'])->name('exams.edit');
        Route::put('/exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
        Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');
        Route::post('/exams/{exam}/unpublish', [ExamController::class, 'unpublish'])->name('exams.unpublish');
        Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');

        // PDF descriptions of managed records
        Route::get('/export/rooms.pdf', [PdfExportController::class, 'rooms'])->name('export.rooms');
        Route::get('/export/teachers.pdf', [PdfExportController::class, 'teachers'])->name('export.teachers');
        Route::get('/export/batches.pdf', [PdfExportController::class, 'batches'])->name('export.batches');
        Route::get('/export/courses.pdf', [PdfExportController::class, 'courses'])->name('export.courses');

        // Raw JSON used while developing the scheduler integration
        Route::get('/scheduler-input', [SchedulerInputController::class, 'show']);
        Route::get('/teacher-course-scores', [TeacherCourseScoreController::class, 'index']);
    });

    // ── Administrators only ──────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });

});
