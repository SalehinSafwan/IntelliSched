<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        return view('teachers.index');
    }

    public function create()
    {
        return view('teachers.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('teachers.index')->with('success', 'Teacher registered successfully!');
    }

    public function show($teacher)
    {
        return view('teachers.show');
    }

    public function edit($teacher)
    {
        return view('teachers.edit');
    }

    public function update(Request $request, $teacher)
    {
        return redirect()->route('teachers.index')->with('success', 'Teacher profile updated successfully!');
    }

    public function preferences(Request $request)
    {
        if ($request->isMethod('post')) {
            return redirect()->route('teachers.preferences')->with('success', 'Teacher course preferences saved!');
        }
        return view('teachers.preferences');
    }

    public function availability(Request $request, $teacher)
    {
        if ($request->isMethod('post')) {
            return redirect()->route('teachers.availability', ['teacher' => $teacher])->with('success', 'Teacher availability timetable saved!');
        }
        return view('teachers.availability');
    }
}
