<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index()
    {
        return view('batches.index');
    }

    public function store(Request $request)
    {
        return redirect()->route('batches.index')->with('success', 'Batch / Section updated!');
    }
}
