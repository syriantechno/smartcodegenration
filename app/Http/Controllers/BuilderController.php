<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;

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

        // 🧭 جلب جميع الجداول من قاعدة البيانات
        $allTables = \DB::select('SHOW TABLES');
        $dbKey = 'Tables_in_' . env('DB_DATABASE');
        $dbTables = [];

        foreach ($allTables as $t) {
            $tableName = $t->$dbKey;
            $columns = \DB::select("SHOW COLUMNS FROM `$tableName`");
            $dbTables[$tableName] = $columns;
        }

        return view('builder.index', compact('savedTables', 'dbTables'));
    }


    public function saveTable(Request $request)
    {
        $tableName = $request->input('table');
        $fields = $request->input('fields');

        if (!$tableName || empty($fields)) {
            return response()->json(['error' => 'اسم الجدول أو الحقول غير صالحة.'], 400);
        }

        $path = storage_path('builder/tables');
        if (!is_dir($path)) mkdir($path, 0777, true);

        file_put_contents("{$path}/{$tableName}.json", json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['status' => 'ok', 'message' => "تم حفظ الجدول {$tableName} بنجاح."]);
    }

    public function injectToDatabase($table)
    {
        $path = storage_path("builder/tables/{$table}.json");

        if (!file_exists($path)) {
            return response()->json(['error' => "❌ Table definition not found: {$table}"], 404);
        }

        // Decode the full JSON file
        $json = json_decode(file_get_contents($path), true);

        // Handle both simple array and structured array formats
        if (isset($json['fields']) && is_array($json['fields'])) {
            $fields = $json['fields'];
        } elseif (is_array($json)) {
            $fields = $json;
        } else {
            return response()->json(['error' => "❌ Invalid table format"], 400);
        }

        // Prevent duplicate creation
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

                    if (!$name || $name === 'id') continue; // skip id (already added)

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
