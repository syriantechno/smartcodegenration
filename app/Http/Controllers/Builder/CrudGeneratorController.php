<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Services\CrudGeneratorService;

class CrudGeneratorController extends Controller
{
    // ✅ صفحة CRUD Generator UI
    public function index()
    {
        // عرض جميع الجداول في قاعدة البيانات
        $tables = DB::select('SHOW TABLES');
        $dbKey = 'Tables_in_' . env('DB_DATABASE');
        $files = array_map(fn($t) => $t->$dbKey, $tables);

        // عند اختيار جدول
        $selected = request('table');
        $fields = [];
        if ($selected) {
            $cols = DB::select("SHOW COLUMNS FROM `$selected`");
            foreach ($cols as $col) {
                $fields[] = [
                    'name' => $col->Field,
                    'type' => $col->Type,
                ];
            }
        }

        return view('builder.crud', compact('files', 'selected', 'fields'));
    }

    // ✅ توليد الكنترولر
    public function generate($table)
    {
        $service = new CrudGeneratorService();
        $result = $service->generateController($table);
        return response()->json($result);
    }

    // ✅ توليد صفحة Index
    public function generateIndex($table)
    {
        $service = new CrudGeneratorService();
        $result = $service->generateIndexView($table);
        return response()->json($result);
    }

    // ✅ توليد صفحة Form (اختياري)
    public function generateForm($table)
    {
        $service = new CrudGeneratorService();
        $result = $service->generateFormView($table);
        return response()->json($result);
    }
}
