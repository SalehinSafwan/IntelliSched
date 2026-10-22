<?php

/*
|--------------------------------------------------------------------------
| IntelliSched option lists
|--------------------------------------------------------------------------
| Fixed vocabularies used by forms and validation. Records themselves
| (rooms, teachers, courses, ...) are managed through the website.
*/

return [

    'roles' => [
        'admin' => 'Administrator',
        'coordinator' => 'Coordinator',
        'teacher' => 'Teacher',
        'student' => 'Student',
    ],

    'room_types' => [
        'THEORY' => 'Theory classroom',
        'LAB' => 'Laboratory',
        'MULTIPURPOSE' => 'Multipurpose (theory and lab)',
    ],

    'course_types' => [
        'THEORY' => 'Theory',
        'LAB' => 'Lab',
    ],

    'statuses' => [
        'ACTIVE' => 'Active',
        'INACTIVE' => 'Inactive',
    ],

    'days' => [
        'SATURDAY', 'SUNDAY', 'MONDAY', 'TUESDAY',
        'WEDNESDAY', 'THURSDAY', 'FRIDAY',
    ],

    'exam_types' => [
        'MIDTERM' => 'Midterm',
        'FINAL' => 'Final',
    ],

    // Every exam of a schedule uses one of these windows.
    'exam_sessions' => [
        'MORNING' => ['start' => '10:00', 'end' => '13:00', 'label' => '10:00 AM – 1:00 PM'],
        'AFTERNOON' => ['start' => '14:00', 'end' => '17:00', 'label' => '2:00 PM – 5:00 PM'],
    ],

    // Theory courses always have exactly this many weekly classes.
    'weekly_theory_classes' => 3,

];
