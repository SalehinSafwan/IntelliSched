<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function show($section)
    {
        return view('sections.show');
    }
}
