<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        return view('exams.index');
    }

    public function store(Request $request)
    {
        return redirect()->route('exams.index')->with('success', 'Exam schedule allocated successfully!');
    }
}
