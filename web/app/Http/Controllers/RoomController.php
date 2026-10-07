<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        return view('rooms.index');
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('rooms.index')->with('success', 'Room registered successfully!');
    }

    public function show(Request $request, $room)
    {
        if ($request->isMethod('post')) {
            return redirect()->route('rooms.show', ['room' => $room])->with('success', 'Lab course eligibility matrix saved!');
        }
        return view('rooms.show');
    }

    public function edit($room)
    {
        return view('rooms.edit');
    }

    public function update(Request $request, $room)
    {
        return redirect()->route('rooms.index')->with('success', 'Room parameters updated successfully!');
    }
}
