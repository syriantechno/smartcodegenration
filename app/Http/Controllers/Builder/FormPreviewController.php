<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FormPreviewController extends Controller
{
    protected string $tablesPath;

    public function __construct()
    {
        $this->tablesPath = storage_path('builder/tables');
    }

    // 🧩 الصفحة الماستر: اختيار جدول + معاينة
    public function master()
    {
        $tableNames = [];
        if (is_dir($this->tablesPath)) {
            foreach (File::files($this->tablesPath) as $file) {
                $tableNames[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return view('builder.form-master', compact('tableNames'));
    }

    // 🎨 صفحة التصميم لجدول واحد
    public function index($table)
    {
        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) {
            abort(404, "Table JSON not found");
        }

        $json = json_decode(File::get($path), true);
        $fields = $json['fields'] ?? [];

        $styles = [
            'tailwind'  => 'Tailwind (default)',
            'bootstrap' => 'Bootstrap 5',
            'midone'    => 'Midone Admin',
            'softui'    => 'Soft UI Dashboard',
        ];

        return view('builder.form-designer', compact('table', 'fields', 'styles'));
    }

    // 📥 تحميل الحقول من ملف JSON (استدعاء AJAX)
    public function loadTableFields(Request $request)
    {
        $table = $request->input('table');
        $path = storage_path("builder/tables/{$table}.json");

        if (!File::exists($path)) {
            return response()->json([
                'status' => 'error',
                'message' => "❌ JSON for table '{$table}' not found."
            ]);
        }

        $json = json_decode(File::get($path), true);
        $fields = $json['fields'] ?? [];

        return response()->json([
            'status' => 'ok',
            'fields' => $fields
        ]);
    }
    public function saveDesign(\Illuminate\Http\Request $request)
    {
        $table = $request->input('table');
        $fields = $request->input('fields');
        $savePath = storage_path("builder/forms/{$table}.json");

        if (!is_dir(dirname($savePath))) {
            mkdir(dirname($savePath), 0777, true);
        }

        \Illuminate\Support\Facades\File::put($savePath, json_encode([
            'table' => $table,
            'fields' => $fields,
            'saved_at' => now()->toDateTimeString()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['status' => 'ok', 'message' => "💾 Form design for '{$table}' saved successfully."]);
    }

}
