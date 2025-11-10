<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BuilderPreviewController extends Controller
{
    protected string $tablesPath;
    protected string $previewPath;

    public function __construct()
    {
        $this->tablesPath = storage_path('builder/tables');
        $this->previewPath = resource_path('views/builder/preview');
        if (!is_dir($this->previewPath)) mkdir($this->previewPath, 0777, true);
    }

    /** 🔹 واجهة عرض المعاينة */
    public function index(Request $request)
    {
        $tables = collect(File::files($this->tablesPath))
            ->map(fn($f) => pathinfo($f, PATHINFO_FILENAME))
            ->values();

        $selected = $request->query('table');
        $fields = [];

        if ($selected && File::exists("{$this->tablesPath}/{$selected}.json")) {
            $json = json_decode(File::get("{$this->tablesPath}/{$selected}.json"), true);
            $fields = $json['fields'] ?? [];
        }

        return view('builder.preview.index', compact('tables', 'selected', 'fields'));
    }

    /** 🔹 تحديث نوع الحقل */
    public function updateFieldType(Request $request)
    {
        $table = $request->input('table');
        $field = $request->input('field');
        $input = $request->input('input');

        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) return response()->json(['error' => 'File not found.'], 404);

        $data = json_decode(File::get($path), true);
        foreach ($data['fields'] as &$f) {
            if ($f['name'] === $field) $f['input'] = $input;
        }
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['status' => 'ok', 'message' => 'Field updated successfully.']);
    }

    /** 🔹 عرض معاينة للفورم فقط */
    public function generateForm($table)
    {
        $path = storage_path("builder/tables/{$table}.json");
        if (!File::exists($path)) {
            return response()->json(['error' => "Table definition not found for '{$table}'"]);
        }

        $json = json_decode(File::get($path), true);
        $fields = $json['fields'] ?? [];

        $html = "<div class='p-6 max-w-3xl mx-auto bg-white rounded-xl shadow-md space-y-6'>";
        $html .= "<h2 class='text-2xl font-bold mb-4'>" . ucfirst($table) . " Form</h2>";
        $html .= "<form action='#' method='POST' class='space-y-4'>";

        $focusCSS = "";

        foreach ($fields as $f) {
            $name  = $f['name'];
            $label = ucfirst(str_replace('_',' ', $f['label'] ?? $name));
            $input = $f['input'] ?? 'text';
            $ui    = $f['ui'] ?? [];

            $borderColor  = $ui['borderColor']  ?? '#cccccc';
            $borderWidth  = $ui['borderWidth']  ?? '1px';
            $borderRadius = $ui['borderRadius'] ?? '6px';
            $boxShadow    = $ui['boxShadow']    ?? '0 1px 3px rgba(0,0,0,0.1)';
            $bgColor      = $ui['background']   ?? '#ffffff';
            $focusColor   = $ui['focusColor']   ?? '#3b82f6';
            $focusWidth   = $ui['focusWidth']   ?? '2px';

            $style = "border: {$borderWidth} solid {$borderColor};
                      border-radius: {$borderRadius};
                      box-shadow: {$boxShadow};
                      background: {$bgColor};
                      transition: all 0.2s ease;";

            $fieldClass = "fld_" . $name;

            $html .= "<div class='flex flex-col space-y-1'>";
            $html .= "<label for='{$name}' class='font-medium text-gray-700'>{$label}</label>";

            if ($input === 'textarea') {
                $html .= "<textarea id='{$name}' class='{$fieldClass} p-2 w-full' style='{$style}'></textarea>";
            } elseif ($input === 'select') {
                $html .= "<select id='{$name}' class='{$fieldClass} p-2 w-full' style='{$style}'><option>Select...</option></select>";
            } elseif ($input === 'switch') {
                $html .= "<label class='inline-flex items-center cursor-pointer {$fieldClass}'><input type='checkbox' class='sr-only peer'><div style='{$style}' class='w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-blue-500 relative after:content-[\"\"] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-5 after:w-5 after:rounded-full after:transition-all peer-checked:after:translate-x-full'></div></label>";
            } else {
                $html .= "<input type='{$input}' id='{$name}' class='{$fieldClass} p-2 w-full' style='{$style}'>";
            }

            $html .= "</div>";

            $focusCSS .= ".{$fieldClass}:focus{
                outline: none !important;
                border-color: {$focusColor} !important;
                box-shadow: 0 0 0 {$focusWidth} {$focusColor}55 !important;
            }";
        }

        $focusCSS .= "input[type=number]::-webkit-inner-spin-button,
                      input[type=number]::-webkit-outer-spin-button {
                          -webkit-appearance: none;
                          margin: 0;
                      }
                      input[type=number] {
                          -moz-appearance: textfield;
                      }";

        if ($focusCSS) {
            $html .= "<style>{$focusCSS}</style>";
        }

        $html .= "<button type='submit' class='px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700'>Save</button>";
        $html .= "</form></div>";

        return response()->json([
            'status'  => 'ok',
            'message' => "✅ Live form preview ready!",
            'html'    => $html
        ]);
    }

    /** 🔹 توليد CRUD كامل */
    public function generateCrud($table)
    {
        $table = strtolower($table);
        $jsonPath = storage_path("builder/tables/{$table}.json");
        if (!file_exists($jsonPath)) {
            return response()->json(['status' => 'error', 'error' => "❌ Table definition not found for '{$table}'."], 404);
        }

        $json = json_decode(file_get_contents($jsonPath), true);
        $fields = collect($json['fields'] ?? [])->unique('name')->values()->toArray();

        /** ✅ Model */
        $modelName = ucfirst($table);
        $modelPath = app_path("Models/{$modelName}.php");
        $fillable = implode("','", array_column($fields, 'name'));

        $belongsTo = [];
        foreach ($fields as $f) {
            if (str_ends_with($f['name'], '_id')) {
                $related = ucfirst(Str::singular(str_replace('_id', '', $f['name'])));
                $belongsTo[] = "    public function {$related}()\n    {\n        return \$this->belongsTo({$related}::class);\n    }\n";
            }
        }

        $modelContent = "<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$modelName} extends Model
{
    protected \$fillable = ['{$fillable}'];

" . implode("\n", $belongsTo) . "
}
";
        File::ensureDirectoryExists(dirname($modelPath));
        File::put($modelPath, $modelContent);

        /** ✅ Controller */
        $controllerName = "{$modelName}Controller";
        $controllerPath = app_path("Http/Controllers/Generated/{$controllerName}.php");
        File::ensureDirectoryExists(dirname($controllerPath));

        $controllerContent = "<?php

namespace App\\Http\\Controllers\\Generated;

use App\\Models\\{$modelName};
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;

class {$controllerName} extends Controller
{
    public function index() {
        \$data = {$modelName}::all();
        return view('generated.{$table}.index', compact('data'));
    }

    public function create() {
        return view('generated.{$table}.create');
    }

    public function store(Request \$r) {
        {$modelName}::create(\$r->all());
        return redirect()->route('{$table}.index');
    }

    public function edit(\$id) {
        \$row = {$modelName}::findOrFail(\$id);
        return view('generated.{$table}.edit', compact('row'));
    }

    public function update(Request \$r, \$id) {
        \$row = {$modelName}::findOrFail(\$id);
        \$row->update(\$r->all());
        return redirect()->route('{$table}.index');
    }

    public function destroy(\$id) {
        {$modelName}::destroy(\$id);
        return redirect()->route('{$table}.index');
    }
}";
        File::put($controllerPath, $controllerContent);

        /** ✅ Views */
        $viewDir = resource_path("views/generated/{$table}");
        File::ensureDirectoryExists($viewDir);

        /** index.blade.php */
        $indexView = <<<BLADE
@extends('layouts.app')
@section('content')
<div class="p-6 max-w-6xl mx-auto">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">📋 {$modelName} List</h2>
    <a href="{{ route('{$table}.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">➕ Add {$modelName}</a>
    <div class="mt-4 overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full border border-gray-200 divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100">
                @php \$cols = count(\$data) ? array_keys(\$data->first()->getAttributes()) : []; @endphp
                <tr>
                    @foreach(\$cols as \$col)
                        <th class="px-4 py-2 text-left text-gray-600">{{ str_replace('_', ' ', \$col) }}</th>
                    @endforeach
                    <th class="px-4 py-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach(\$data as \$row)
                <tr class="border-t hover:bg-gray-50">
                    @foreach(\$row->getAttributes() as \$val)
                        <td class="px-4 py-2">{{ \$val }}</td>
                    @endforeach
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('{$table}.edit', \$row->id) }}" class="text-blue-600">✏️</a>
                        <form method="POST" action="{{ route('{$table}.destroy', \$row->id) }}" class="inline">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('Are you sure?')" class="text-red-600 ml-2">🗑️</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
BLADE;
        File::put("{$viewDir}/index.blade.php", $indexView);

        /** create/edit forms */
        $formContent = "@extends('layouts.app')\n@section('content')\n<div class='p-6 max-w-3xl mx-auto bg-white rounded-xl shadow-md space-y-6'>\n<h2 class='text-2xl font-bold mb-4'>Create " . ucfirst($table) . "</h2>\n<form method='POST' action='{{ route(\"{$table}.store\") }}' class='space-y-4'>\n@csrf\n";

        foreach ($fields as $f) {
            $name = $f['name'];
            $label = ucfirst(str_replace('_',' ', $f['label'] ?? $name));
            $input = $f['input'] ?? 'text';
            $ui = $f['ui'] ?? [];
            $style = '';
            foreach ($ui as $k => $v) $style .= "{$k}:{$v};";

            $formContent .= "<div class='flex flex-col'>\n<label for='{$name}' class='font-medium text-gray-700 mb-1'>{$label}</label>\n";

            if ($input === 'textarea') {
                $formContent .= "<textarea name='{$name}' id='{$name}' style='{$style}' class='border p-2 w-full focus:ring-2 focus:ring-blue-300'></textarea>\n";
            } elseif ($input === 'select') {
                if (!empty($f['relation']) && str_contains($f['relation'], '.')) {
                    [$relatedTable, $relatedColumn] = explode('.', $f['relation']);
                    $relatedModel = ucfirst(Str::singular($relatedTable));
                    $formContent .= "<?php \$options = App\\Models\\{$relatedModel}::all(); ?>\n";
                    $formContent .= "<select name='{$name}' id='{$name}' style='{$style}' class='border p-2 w-full'>\n<option value=''>Select...</option>\n@foreach(\$options as \$opt)<option value='{{ \$opt->id }}'>{{ \$opt->{$relatedColumn} }}</option>@endforeach\n</select>\n";
                }
            } else {
                $formContent .= "<input type='{$input}' name='{$name}' id='{$name}' style='{$style}' class='border p-2 w-full focus:ring-2 focus:ring-blue-300'>\n";
            }
            $formContent .= "</div>\n";
        }

        $formContent .= "<button type='submit' class='px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700'>💾 Save</button>\n</form>\n</div>\n@endsection";
        File::put("{$viewDir}/create.blade.php", $formContent);
        File::put("{$viewDir}/edit.blade.php", $formContent);

        return response()->json([
            'status' => 'ok',
            'message' => "✅ Full CRUD + Form preview generated for '{$table}'!",
            'model' => $modelPath,
            'controller' => $controllerPath,
            'views' => [$viewDir]
        ]);
    }
}
