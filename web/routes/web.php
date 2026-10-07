<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TeacherCourseScoreController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\TimeSlotController;


Route::get(
    '/teacher-course-scores',
    [TeacherCourseScoreController::class, 'index']
);


// Root redirect to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Application Routes
Route::middleware('auth')->group(function () {

    // Main Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Role Specific Aliases (redirect to dashboard view with role query)
    Route::get('/admin/dashboard', function() {
        return redirect()->route('dashboard', ['preview_role' => 'ADMIN']);
    })->name('admin.dashboard');

    Route::get('/coordinator/dashboard', function() {
        return redirect()->route('dashboard', ['preview_role' => 'COORDINATOR']);
    })->name('coordinator.dashboard');

    Route::get('/teacher/dashboard', function() {
        return redirect()->route('dashboard', ['preview_role' => 'TEACHER']);
    })->name('teacher.dashboard');

    Route::get('/student/dashboard', function() {
        return redirect()->route('dashboard', ['preview_role' => 'STUDENT']);
    })->name('student.dashboard');

    // Courses Management
    Route::resource('courses', CourseController::class);

    // Teachers Management & Preferences & Availability
    Route::match(['get', 'post'], '/teachers/preferences', [TeacherController::class, 'preferences'])->name('teachers.preferences');
    Route::match(['get', 'post'], '/teachers/{teacher}/availability', [TeacherController::class, 'availability'])->name('teachers.availability');
    Route::resource('teachers', TeacherController::class);

    // Rooms Management & Lab Course Eligibility
    Route::match(['get', 'post'], '/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
    Route::resource('rooms', RoomController::class)->except(['show']);

    // Batches & Sections
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
    Route::get('/sections/{section}', [SectionController::class, 'show'])->name('sections.show');

    // Time Slots Management
    Route::get('/timeslots', [TimeSlotController::class, 'index'])->name('timeslots.index');
    Route::post('/timeslots', [TimeSlotController::class, 'store'])->name('timeslots.store');

    // Scheduling Engine & Routine Visualization
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
    Route::get('/schedules/conflicts', [ScheduleController::class, 'conflicts'])->name('schedules.conflicts');

    // Examinations
    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');

    // Audit Logs
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

});
