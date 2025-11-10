<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Builder;
use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class BuilderController extends Controller
{

    public function index()
    {
        $path = storage_path('builder/tables');
        if (!is_dir($path)) mkdir($path, 0777, true);

        $files = glob($path . '/*.json');
        $savedTables = [];
        foreach ($files as $file) {
            $savedTables[] = basename($file, '.json');
        }

        // 🧭 Fetch all DB tables and columns
        $allTables = \DB::select('SHOW TABLES');
        $dbKey = 'Tables_in_' . env('DB_DATABASE');
        $dbTables = [];

        foreach ($allTables as $t) {
            $tableName = $t->$dbKey;
            $columns = \DB::select("SHOW COLUMNS FROM `$tableName`");
            $dbTables[] = $tableName;

        }

        return view('builder.tables', compact('savedTables', 'dbTables'));

    }

    public function saveTable(Request $request)
    {
        $tableName = $request->input('table');
        $fields = $request->input('fields');

        // أحيانًا بتجي الحقول كـ JSON string من الواجهة
        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $fields = $decoded;
            }
        }

        // التحقق من البيانات
        if (!$tableName || empty($fields)) {
            return response()->json(['error' => 'اسم الجدول أو الحقول غير صالحة.'], 400);
        }

        // إنشاء مجلد التخزين إن لم يكن موجود
        $path = storage_path('builder/tables');
        if (!is_dir($path)) mkdir($path, 0777, true);

        // ✅ هنا بالضبط نستدعي العلاقات الذكية
        $relationsMap = Builder\BuilderRelationsController::getAllRelations();

        // 🔹 نبدأ المعالجة وتوليد المخطط الذكي
        $normalized = [];
        foreach ($fields as $f) {
            if (!isset($f['name'])) continue;

            $name = $f['name'];
            $type = $f['type'] ?? 'string';

            // نبدأ بالتخمين الافتراضي
            $relation = $this->guessRelation($name);

            // إذا عندنا تعريف علاقة حقيقي من relations.json نعتمده
            if (isset($relationsMap[$tableName][$name])) {
                $relation = $relationsMap[$tableName][$name];
            }

            $normalized[] = [
                'name' => $name,
                'type' => $type,
                'label' => ucfirst(str_replace('_', ' ', $name)),
                'input' => $this->guessInputType($type, $name),
                'required' => $f['required'] ?? false,
                'in_table' => true,
                'relation' => $relation
            ];
        }

        // 🔹 نحفظ ملف JSON النهائي
        $schema = [
            'table' => $tableName,
            'fields' => $normalized
        ];

        file_put_contents("{$path}/{$tableName}.json", json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['status' => 'ok', 'message' => "تم حفظ الجدول {$tableName} بنجاح."]);
    }



    private function guessInputType($type, $name)
    {
        $map = [
            'text' => 'textarea',
            'date' => 'date',
            'boolean' => 'switch',
            'tinyint' => 'switch',
            'decimal' => 'number',
            'integer' => 'number',
            'bigint' => 'number'
        ];

        if (str_ends_with($name, '_id')) return 'select';
        if (str_contains($name, 'email')) return 'email';
        if (str_contains($name, 'password')) return 'password';
        if (str_contains($name, 'desc') || str_contains($name, 'content')) return 'textarea';

        return $map[$type] ?? 'text';
    }

    private function guessRelation($name)
    {
        if (str_ends_with($name, '_id')) {
            $table = str_replace('_id', 's', $name);
            return "{$table}.name";
        }
        return null;
    }

    public function injectToDatabase($table)
    {
        $path = storage_path("builder/tables/{$table}.json");

        if (!file_exists($path)) {
            return response()->json(['error' => "❌ Table definition not found: {$table}"], 404);
        }

        $json = json_decode(file_get_contents($path), true);

        // Handle both old and new formats
        $fields = $json['fields'] ?? $json;

        if (!is_array($fields)) {
            return response()->json(['error' => "❌ Invalid table format"], 400);
        }

        if (Schema::hasTable($table)) {
            return response()->json(['error' => "⚠️ Table '{$table}' already exists"], 400);
        }

        try {
            Schema::create($table, function (Blueprint $t) use ($fields) {
                $t->id();

                foreach ($fields as $f) {
                    if (!is_array($f)) continue;
                    $name = $f['name'] ?? null;
                    $type = $f['type'] ?? 'string';
                    if (!$name || $name === 'id') continue;

                    switch ($type) {
                        case 'integer': $t->integer($name)->nullable(); break;
                        case 'decimal': $t->decimal($name, 10, 2)->nullable(); break;
                        case 'boolean': $t->boolean($name)->default(false); break;
                        case 'date': $t->date($name)->nullable(); break;
                        case 'text': $t->text($name)->nullable(); break;
                        default: $t->string($name)->nullable();
                    }
                }

                $t->timestamps();
            });

            Log::info("✅ Table '{$table}' created successfully.");
            return response()->json(['status' => 'ok', 'message' => "✅ Table '{$table}' created successfully!"]);

        } catch (\Throwable $e) {
            Log::error("❌ Failed to create table {$table}: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
