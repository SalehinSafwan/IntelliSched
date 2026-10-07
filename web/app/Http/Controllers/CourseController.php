<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        return view('courses.index');
    }

    public function create()
    {
        return view('courses.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('courses.index')->with('success', 'Course created successfully!');
    }

    public function show($course)
    {
        return view('courses.show');
    }

    public function edit($course)
    {
        return view('courses.edit');
    }

    public function update(Request $request, $course)
    {
        return redirect()->route('courses.index')->with('success', 'Course updated successfully!');
    }

    public function destroy($course)
    {
        return redirect()->route('courses.index')->with('success', 'Course deleted successfully!');
    }
}
