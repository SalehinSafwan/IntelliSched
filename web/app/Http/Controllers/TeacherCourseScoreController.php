<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Teacher;
use App\Services\Teacher\TeacherCourseScoringService;

class TeacherCourseScoreController extends Controller
{
    public function index(
        TeacherCourseScoringService $scoringService
    ) {
        $teachers = Teacher::with([
            'user',
            'expertise',
            'preferences',
            'teachingHistory',
        ])->get();

        $courses = Course::all();

        $scores = [];

        foreach ($teachers as $teacher) {

            foreach ($courses as $course) {

                $scores[$teacher->id][$course->id] =
                    $scoringService->calculate(
                        $teacher,
                        $course
                    );
            }
        }

        return response()->json([
            'teachers' => $teachers,
            'courses' => $courses,
            'scores' => $scores,
        ]);
    }
}