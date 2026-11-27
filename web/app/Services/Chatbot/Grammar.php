<?php

namespace App\Services\Chatbot;

final class Grammar
{
    // Intents
    public const HELP = 'HELP';
    public const DAY_SCHEDULE = 'DAY_SCHEDULE';
    public const WEEK_SCHEDULE = 'WEEK_SCHEDULE';
    public const COUNT_CLASSES = 'COUNT_CLASSES';
    public const CURRENT_CLASS = 'CURRENT_CLASS';
    public const NEXT_CLASS = 'NEXT_CLASS';
    public const FIRST_CLASS = 'FIRST_CLASS';
    public const LAST_CLASS = 'LAST_CLASS';
    public const FREE_SLOTS = 'FREE_SLOTS';
    public const COURSE_WHEN = 'COURSE_WHEN';
    public const COURSE_TEACHER = 'COURSE_TEACHER';
    public const COURSE_ROOM = 'COURSE_ROOM';
    public const TEACHER_CLASSES = 'TEACHER_CLASSES';
    public const TEACHER_COURSES = 'TEACHER_COURSES';
    public const MY_COURSES = 'MY_COURSES';
    public const MY_TEACHERS = 'MY_TEACHERS';

    public const NONTERMINALS = [
        'subject' => ['CLASS|COURSE_KW* COURSE CLASS|COURSE_KW*'],
        'tutor' => ['TEACHER_KW? TEACHER TEACHER_KW?'],
        'time' => ['DATE', 'DAY'],
    ];

    public const RULES = [
        [self::HELP, 'HELP'],

        // how many classes today / how many classes this week / how many courses
        [self::COUNT_CLASSES, 'COUNT WEEK? CLASS time?'],
        [self::COUNT_CLASSES, 'COUNT CLASS WEEK'],
        [self::MY_COURSES, 'COUNT COURSE_KW'],

        // class now / what class is running now / who is teaching now
        [self::CURRENT_CLASS, 'WHERE|WHO? CLASS|TEACHES? NOW CLASS?'],

        // next class / where is my next class / when is the next CSE 3201 class
        [self::NEXT_CLASS, 'WHEN|WHERE|WHO? CLASS? NEXT CLASS?'],
        [self::NEXT_CLASS, 'WHEN|WHERE|WHO? NEXT subject'],
        [self::NEXT_CLASS, 'subject NEXT'],

        // first class tomorrow / when does my class start today / last class today
        [self::FIRST_CLASS, 'WHEN|WHERE|WHO? FIRST CLASS? time?'],
        [self::FIRST_CLASS, 'WHEN? CLASS FIRST time?'],
        [self::FIRST_CLASS, 'time FIRST CLASS?'],
        [self::LAST_CLASS, 'WHEN|WHERE|WHO? LAST CLASS? time?'],
        [self::LAST_CLASS, 'WHEN? CLASS LAST time?'],
        [self::LAST_CLASS, 'time LAST CLASS?'],

        // am I free today / free periods on Monday / when am I free tomorrow
        [self::FREE_SLOTS, 'WHEN? FREE CLASS? time?'],
        [self::FREE_SLOTS, 'time FREE CLASS?'],

        // who teaches CSE 3201 / CSE 3201 class by which teacher / teacher of CSE 3201
        [self::COURSE_TEACHER, 'WHO TEACHES|TEACHER_KW? subject'],
        [self::COURSE_TEACHER, 'TEACHER_KW subject'],
        [self::COURSE_TEACHER, 'WHO? subject TEACHER_KW'],
        [self::COURSE_TEACHER, 'subject TEACHES? BY WHO'],
        [self::COURSE_TEACHER, 'subject WHO TEACHES?'],

        // where is CSE 3201 class / CSE 3201 room / which room is CSE 3201 on Monday
        [self::COURSE_ROOM, 'WHERE ROOM_KW? subject time?'],
        [self::COURSE_ROOM, 'ROOM_KW subject time?'],
        [self::COURSE_ROOM, 'subject WHERE|ROOM_KW time?'],

        // when is CSE 3201 class / CSE 3201 class when / CSE 3201 today
        [self::COURSE_WHEN, 'WHEN subject time?'],
        [self::COURSE_WHEN, 'subject WHEN time?'],
        [self::COURSE_WHEN, 'subject time WHEN?'],
        [self::COURSE_WHEN, 'time subject'],
        [self::COURSE_WHEN, 'subject'],

        // what does Rahman teach / courses of Rahman
        [self::TEACHER_COURSES, 'COURSE_KW? tutor TEACHES COURSE_KW?'],
        [self::TEACHER_COURSES, 'COURSE_KW BY? tutor'],

        // when is Rahman sir's class / classes by Rahman today
        [self::TEACHER_CLASSES, 'WHEN|WHERE? tutor CLASS? time?'],
        [self::TEACHER_CLASSES, 'tutor CLASS? WHEN|WHERE time?'],
        [self::TEACHER_CLASSES, 'WHEN|WHERE? CLASS BY? tutor time?'],
        [self::TEACHER_CLASSES, 'time CLASS? BY? tutor'],

        // today's class / classes on Monday / what do I have tomorrow
        [self::DAY_SCHEDULE, 'WHEN|WHERE|WHO? CLASS time'],
        [self::DAY_SCHEDULE, 'time CLASS?'],

        // my courses / which courses do I teach / what do I teach
        [self::MY_COURSES, 'WEEK? COURSE_KW TEACHES?'],
        [self::MY_COURSES, 'TEACHES COURSE_KW?'],

        // who are my teachers / my co-teachers
        [self::MY_TEACHERS, 'WHO? TEACHER_KW'],

        // weekly routine / my classes / full schedule / this week
        [self::WEEK_SCHEDULE, 'WHEN|WHERE? WEEK? CLASS WEEK?'],
        [self::WEEK_SCHEDULE, 'WEEK'],
    ];
}
