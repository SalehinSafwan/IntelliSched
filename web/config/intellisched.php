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

    // Student class representatives who receive teacher messages.
    'class_reps' => [
        'CR' => 'Class Representative (CR)',
        'ACR' => 'Assistant Class Representative (ACR)',
    ],

    // Teacher → CR/ACR message types: [label, icon, colour class].
    'message_categories' => [
        'RESCHEDULE' => ['label' => 'Class rescheduled', 'icon' => 'bi-arrow-repeat', 'tone' => 'warning'],
        'CANCEL' => ['label' => 'Class cancelled', 'icon' => 'bi-x-octagon', 'tone' => 'danger'],
        'EXTRA_CLASS' => ['label' => 'Extra / make-up class', 'icon' => 'bi-plus-circle', 'tone' => 'success'],
        'ROOM_CHANGE' => ['label' => 'Room change', 'icon' => 'bi-door-open', 'tone' => 'info'],
        'ASSESSMENT' => ['label' => 'Quiz / assignment', 'icon' => 'bi-pencil-square', 'tone' => 'info'],
        'GENERAL' => ['label' => 'General notice', 'icon' => 'bi-megaphone', 'tone' => 'neutral'],
    ],

    // One-tap phrases for the CR's notice editor.
    'notice_snippets' => [
        'Everyone please take note.',
        'Attendance is mandatory.',
        'Please be on time.',
        'Bring your calculator.',
        'Bring your lab report.',
        'Please share with everyone who is not in this group.',
        'Contact me if you have any questions.',
    ],

    // Stages of a message session. Only COMPLETED is closed.
    'message_statuses' => [
        'AWAITING_NOTICE' => ['label' => 'Awaiting notice', 'icon' => 'bi-hourglass-split', 'tone' => 'info'],
        'PENDING_REVIEW' => ['label' => 'Notice pending review', 'icon' => 'bi-eye', 'tone' => 'warning'],
        'CHANGES_REQUESTED' => ['label' => 'Correction requested', 'icon' => 'bi-arrow-counterclockwise', 'tone' => 'danger'],
        'COMPLETED' => ['label' => 'Completed', 'icon' => 'bi-check2-circle', 'tone' => 'success'],
    ],

    // Schedule assistant (rule-based chatbot for teachers and students).
    'chatbot' => [
        // Local clock used for "today", "tomorrow", "next class" and
        // "class now". The application itself stores times in UTC.
        'timezone' => env('INTELLISCHED_TIMEZONE', 'Asia/Dhaka'),

        // Longest question accepted, in characters.
        'max_length' => 200,
    ],

];
