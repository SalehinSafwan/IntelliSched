<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\Course;

class TeacherCourseScoringService
{
    private const EXPERTISE_WEIGHT = 0.40;
    private const HISTORY_WEIGHT = 0.30;
    private const PREFERENCE_WEIGHT = 0.20;
    private const WORKLOAD_WEIGHT = 0.10;

    public function calculate(
        Teacher $teacher,
        Course $course
    ): float {
        $expertise = $this->getExpertiseScore(
            $teacher,
            $course
        );

        $history = $this->getHistoryScore(
            $teacher,
            $course
        );

        $preference = $this->getPreferenceScore(
            $teacher,
            $course
        );

        $workload = $this->getWorkloadScore(
            $teacher
        );

        $score =
            ($expertise * self::EXPERTISE_WEIGHT) +
            ($history * self::HISTORY_WEIGHT) +
            ($preference * self::PREFERENCE_WEIGHT) +
            ($workload * self::WORKLOAD_WEIGHT);

        return round($score, 2);
    }

    private function getExpertiseScore(
        Teacher $teacher,
        Course $course
    ): float {
        $expertise = $teacher->expertise()
            ->where('course_id', $course->id)
            ->first();

        return $expertise
            ? (float) $expertise->expertise_score
            : 0.0;
    }

    private function getPreferenceScore(
        Teacher $teacher,
        Course $course
    ): float {
        $preference = $teacher->preferences()
            ->where('course_id', $course->id)
            ->first();

        return $preference
            ? (float) $preference->preference_score
            : 0.0;
    }

    private function getHistoryScore(
        Teacher $teacher,
        Course $course
    ): float {
        $history = $teacher->teachingHistory()
            ->where('course_id', $course->id)
            ->sum('times_taught');

        /*
          Teaching history represented as a range of 0-100.
         
          For the initial version: 5 or more previous teaching experiences = 100.
        */

        return min(($history / 5) * 100, 100);
    }

    private function getWorkloadScore(
        Teacher $teacher
    ): float {
        /*
         * At this stage we do not yet have generated
         * schedule entries, so we use the teacher's
         * configured maximum as the baseline.
         *
         * This will be refined once active schedules
         * are incorporated.
         */

        $max = $teacher->max_weekly_classes;

        if ($max <= 0) {
            return 0;
        }

        return 100;
    }
}