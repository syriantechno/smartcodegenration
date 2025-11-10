<?php

namespace App\Http\Controllers\Generated;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    protected string $table = 'employees';

    public function index()
    {
        $records = DB::table($this->table)->paginate(10);
        return view('generated.employees_index', compact('records'));
    }

    public function create()
    {
        return view('generated.employees_form');
    }

    public function store(Request $request)
    {
        DB::table($this->table)->insert($request->except('_token'));
        return redirect()->route('employees.index')->with('success', 'Created successfully');
    }

    public function edit($id)
    {
        $item = DB::table($this->table)->find($id);
        return view('generated.employees_form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        DB::table($this->table)->where('id', $id)->update($request->except('_token'));
        return redirect()->route('employees.index')->with('success', 'Updated successfully');
    }

    public function destroy($id)
    {
        DB::table($this->table)->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Deleted');
    }
}
