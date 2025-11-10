<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudGeneratorController extends Controller
{
    protected string $tablesPath;

    public function __construct()
    {
        $this->tablesPath = storage_path('builder/tables');
        if (!is_dir($this->tablesPath)) {
            mkdir($this->tablesPath, 0777, true);
        }
    }

    public function index(Request $request)
    {
        $files = [];
        foreach (File::files($this->tablesPath) as $file) {
            $files[] = pathinfo($file, PATHINFO_FILENAME);
        }

        $selected = $request->query('table');
        $fields = [];

        if ($selected && File::exists("{$this->tablesPath}/{$selected}.json")) {
            $json = json_decode(File::get("{$this->tablesPath}/{$selected}.json"), true);

            if (isset($json['fields'])) {
                $fields = $json['fields'];
            } elseif (is_array($json)) {
                $fields = collect($json)
                    ->map(fn($col) => [
                        'name' => $col['name'] ?? $col['Field'] ?? null,
                        'type' => $col['type'] ?? $col['Type'] ?? 'string',
                        'required' => $col['required'] ?? false,
                    ])
                    ->filter(fn($f) => !empty($f['name']))
                    ->values()
                    ->toArray();
            }
        }

        return view('builder.crud', compact('files', 'selected', 'fields'));
    }

    public function generate(Request $request)
    {
        $table = $request->input('table');
        $fieldsRaw = $request->input('fields', []);

        // 🧠 fix: extract field names correctly
        $fields = collect($fieldsRaw)->map(function ($f) {
            if (is_array($f) && isset($f['name'])) return $f['name'];
            if (is_string($f)) return $f;
            return null;
        })->filter()->values()->toArray();

        if (!$table || empty($fields)) {
            return response()->json(['error' => '⚠️ لم يتم تحديد الجدول أو الحقول.'], 400);
        }

        $modelName = Str::studly(Str::singular($table));
        $controllerDir = app_path('Http/Controllers/Generated');
        $viewDir = resource_path("views/generated/{$table}");

        if (!is_dir($controllerDir)) mkdir($controllerDir, 0777, true);
        if (!is_dir($viewDir)) mkdir($viewDir, 0777, true);

        // ✅ توليد الموديل
        $fillable = "['" . implode("','", $fields) . "']";
        $modelCode = <<<PHP
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class {$modelName} extends Model
{
    protected \$table = '{$table}';
    protected \$fillable = {$fillable};
}
PHP;
        file_put_contents(app_path("Models/{$modelName}.php"), $modelCode);

        // ✅ توليد الكونترولر
        $controllerCode = <<<PHP
<?php
namespace App\Http\Controllers\Generated;

use App\Http\Controllers\Controller;
use App\Models\\{$modelName};
use Illuminate\Http\Request;

class {$modelName}Controller extends Controller
{
    public function index() {
        \$items = {$modelName}::all();
        return view('generated.{$table}.index', compact('items'));
    }

    public function store(Request \$request) {
        {$modelName}::create(\$request->all());
        return back()->with('success', 'تم الحفظ بنجاح!');
    }
}
PHP;
        file_put_contents("{$controllerDir}/{$modelName}Controller.php", $controllerCode);

        // ✅ توليد الواجهة
        $viewCode = <<<BLADE
@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-xl font-semibold mb-4">{$modelName} Records</h1>
    <form method="POST" action="">
        @csrf
        <div class="grid grid-cols-2 gap-4 mb-4">
BLADE;

        foreach ($fields as $field) {
            $viewCode .= "\n            <input type='text' name='{$field}' placeholder='{$field}' class='form-control' />";
        }

        $viewCode .= <<<BLADE
        </div>
        <button class="btn btn-primary">Save</button>
    </form>

    <table class="table mt-6">
        <thead><tr><th>ID</th>
BLADE;

        foreach ($fields as $field) {
            $viewCode .= "<th>{$field}</th>";
        }

        $viewCode .= <<<BLADE
        </tr></thead>
        <tbody>
            @foreach(\$items as \$item)
            <tr><td>{{ \$item->id }}</td>
BLADE;

        foreach ($fields as $field) {
            $viewCode .= "<td>{{ \$item->{$field} }}</td>";
        }

        $viewCode .= <<<BLADE
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
BLADE;

        file_put_contents("{$viewDir}/index.blade.php", $viewCode);

        return response()->json([
            'success' => true,
            'model' => "App\\Models\\{$modelName}",
            'controller' => "App\\Http\\Controllers\\Generated\\{$modelName}Controller",
            'view' => "resources/views/generated/{$table}/index.blade.php",
            'route' => "Route::resource('/generated/{$table}', App\\Http\\Controllers\\Generated\\{$modelName}Controller::class);"
        ]);
    }

}
