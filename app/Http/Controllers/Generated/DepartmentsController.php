<?php

namespace App\Http\Controllers\Generated;

use App\Models\Departments;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DepartmentsController extends Controller
{
    public function index() { $data = Departments::all(); return view('generated.departments.index', compact('data')); }
    public function create() { return view('generated.departments.create'); }
    public function store(Request $r) { Departments::create($r->all()); return redirect()->route('departments.index'); }
}
