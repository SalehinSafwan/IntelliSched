<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function index()
    {
        return view('timeslots.index');
    }

    public function store(Request $request)
    {
        return redirect()->route('timeslots.index')->with('success', 'Time slot created successfully!');
    }
}
