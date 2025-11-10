<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BuilderFormController extends Controller
{
    protected string $tablesPath;
    protected string $relationsPath;

    public function __construct()
    {
        $this->tablesPath    = storage_path('builder/tables');
        $this->relationsPath = storage_path('builder/relations.json');
    }

    public function index(Request $request)
    {
        // قائمة الجداول
        $tables = collect(File::files($this->tablesPath))
            ->map(fn($f) => pathinfo($f, PATHINFO_FILENAME))
            ->values();

        // عند اختيار جدول
        $selected = $request->query('table');
        $fields = [];
        $lookups = [];

        if ($selected && File::exists("{$this->tablesPath}/{$selected}.json")) {
            $tableData = json_decode(File::get("{$this->tablesPath}/{$selected}.json"), true);
            $fields = $tableData['fields'] ?? [];

            // العلاقات لمعالجة الحقول المنتهية بـ _id
            if (File::exists($this->relationsPath)) {
                $relations = json_decode(File::get($this->relationsPath), true);
                foreach ($relations as $rel) {
                    if ($rel['relation_type'] === 'belongsTo' && $rel['table_a'] === $selected) {
                        $target = $rel['table_b'];
                        if (DB::getSchemaBuilder()->hasTable($target)) {
                            $col = $this->guessLabelColumn($target);
                            $lookups["{$target}_id"] =
                                DB::table($target)->pluck($col, 'id')->toArray();
                        }
                    }
                }
            }
        }

        return view('builder.form', compact('tables', 'selected', 'fields', 'lookups'));
    }

    public function store(Request $request)
    {
        $table = $request->input('table');
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return response()->json(['error' => "Table '{$table}' not found."], 404);
        }

        $data = $request->except(['_token', 'table']);
        DB::table($table)->insert($data);

        return response()->json(['status' => 'ok', 'message' => '✅ Record saved successfully']);
    }

    private function guessLabelColumn($table)
    {
        $cols = DB::getSchemaBuilder()->getColumnListing($table);
        foreach (['name','title','arabic_name','english_name','code'] as $c)
            if (in_array($c, $cols)) return $c;
        return $cols[1] ?? 'id';
    }
}
