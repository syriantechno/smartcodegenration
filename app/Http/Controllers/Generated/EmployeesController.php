<?php

namespace App\Http\Controllers\Generated;

use App\Models\Employees;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
    public function index() {
        $data = Employees::all();
        return view('generated.employees.index', compact('data'));
    }

    public function create() {
        return view('generated.employees.create');
    }

    public function store(Request $r) {
        Employees::create($r->all());
        return redirect()->route('employees.index');
    }

    public function edit($id) {
        $row = Employees::findOrFail($id);
        return view('generated.employees.edit', compact('row'));
    }

    public function update(Request $r, $id) {
        $row = Employees::findOrFail($id);
        $row->update($r->all());
        return redirect()->route('employees.index');
    }

    public function destroy($id) {
        Employees::destroy($id);
        return redirect()->route('employees.index');
    }
}