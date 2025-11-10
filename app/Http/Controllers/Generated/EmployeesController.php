<?php

namespace App\Http\Controllers\Generated;

use App\Http\Controllers\Controller;
use App\Models\Employees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeesController extends Controller
{
    public function index()
    {
        $items = Employees::all();
        $departments = DB::table('departments')->pluck('name', 'id'); // يجلب قائمة الإدارات
        return view('generated.employees.index', compact('items', 'departments'));
    }

    public function store(Request $request)
    {
        Employees::create($request->all());
        return back()->with('success', 'تم الحفظ بنجاح!');
    }
}
