<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BuilderRelationsController extends Controller
{
    protected string $relationsPath;
    protected string $tablesPath;

    public function __construct()
    {
        // المسارات المطلقة المؤكدة
        $this->relationsPath = storage_path('builder/relations.json');
        $this->tablesPath    = storage_path('builder/tables');

        // تأكد من وجود المجلدات
        if (!is_dir(storage_path('builder'))) {
            mkdir(storage_path('builder'), 0777, true);
        }
        if (!is_dir($this->tablesPath)) {
            mkdir($this->tablesPath, 0777, true);
        }
    }

    public function index()
    {
        // ✅ قراءة ملفات الجداول من storage/builder/tables
        $files = [];
        foreach (File::files($this->tablesPath) as $file) {
            $files[] = pathinfo($file, PATHINFO_FILENAME);
        }

        // ✅ قراءة العلاقات من relations.json إذا موجود
        $relations = File::exists($this->relationsPath)
            ? json_decode(File::get($this->relationsPath), true)
            : [];

        return view('builder.relations', compact('files', 'relations'));
    }

    public function save(Request $request)
    {
        $data = [
            'table_a'       => $request->input('table_a'),
            'relation_type' => $request->input('relation_type'),
            'table_b'       => $request->input('table_b'),
        ];

        // ✅ تأكد من وجود المجلد قبل الحفظ
        if (!is_dir(dirname($this->relationsPath))) {
            mkdir(dirname($this->relationsPath), 0777, true);
        }

        $relations = File::exists($this->relationsPath)
            ? json_decode(File::get($this->relationsPath), true)
            : [];

        $relations[] = $data;
        File::put($this->relationsPath, json_encode($relations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json([
            'status' => 'ok',
            'count'  => count($relations),
        ]);
    }

    public function inject($index)
    {
        if (!File::exists($this->relationsPath)) {
            return response()->json(['error' => 'No relations file found.']);
        }

        $relations = json_decode(File::get($this->relationsPath), true);
        if (!isset($relations[$index])) {
            return response()->json(['error' => 'Relation not found.']);
        }

        $r = $relations[$index];
        $tableA = $r['table_a'];
        $tableB = $r['table_b'];
        $type   = $r['relation_type'];

        if ($type === 'belongsTo') {
            if (!Schema::hasColumn($tableA, "{$tableB}_id")) {
                Schema::table($tableA, function ($table) use ($tableB) {
                    $table->unsignedBigInteger("{$tableB}_id")->nullable();
                });
            }
            $msg = "✅ Added column {$tableB}_id to {$tableA}";
        } elseif ($type === 'hasMany' || $type === 'hasOne') {
            if (!Schema::hasColumn($tableB, "{$tableA}_id")) {
                Schema::table($tableB, function ($table) use ($tableA) {
                    $table->unsignedBigInteger("{$tableA}_id")->nullable();
                });
            }
            $msg = "✅ Added column {$tableA}_id to {$tableB}";
        } else {
            $msg = "ℹ️ belongsToMany not implemented yet.";
        }

        return response()->json(['message' => $msg]);
    }
}
