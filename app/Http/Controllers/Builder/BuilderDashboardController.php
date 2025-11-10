<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;

class BuilderDashboardController extends Controller
{
    public function index()
    {
        return view('builder.dashboard');
    }
}
