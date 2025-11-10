<?php
namespace App\Http\Controllers\Generated;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index() {
        $items = Department::all();
        return view('generated.departments.index', compact('items'));
    }

    public function store(Request $request) {
        Department::create($request->all());
        return back()->with('success', 'تم الحفظ بنجاح!');
    }
}