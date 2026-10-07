<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TeacherCourseScoreController;

Route::get(
    '/teacher-course-scores',
    [TeacherCourseScoreController::class, 'index']
);

Route::get('/', function () {
    return view('welcome');
});
