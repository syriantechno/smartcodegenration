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
        $this->tablesPath  = storage_path('builder/tables');
        $this->previewPath = resource_path('views/builder/preview');

        if (!is_dir($this->previewPath)) {
            mkdir($this->previewPath, 0777, true);
        }
    }

    /**
     * 🔹 صفحة معاينة قديمة (لو لسا مستخدمة بشي رابط)
     */
    public function index(Request $request)
    {
        $tables = collect(File::files($this->tablesPath))
            ->map(fn($f) => pathinfo($f, PATHINFO_FILENAME))
            ->values();

        $selected = $request->query('table');
        $fields   = [];

        if ($selected && File::exists("{$this->tablesPath}/{$selected}.json")) {
            $json   = json_decode(File::get("{$this->tablesPath}/{$selected}.json"), true);
            $fields = $json['fields'] ?? [];
        }

        return view('builder.preview.index', compact('tables', 'selected', 'fields'));
    }

    /**
     * 🔹 تحديث نوع الـ input للحقل من المصمم
     */
    public function updateFieldType(Request $request)
    {
        $table = $request->input('table');
        $field = $request->input('field');
        $input = $request->input('input');

        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        $data = json_decode(File::get($path), true);
        foreach ($data['fields'] as &$f) {
            if (($f['name'] ?? null) === $field) {
                $f['input'] = $input;
                break;
            }
        }
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['status' => 'ok', 'message' => 'Field updated successfully.']);
    }

    /**
     * 🔹 حفظ إعدادات الـ UI (borderColor, focusColor, boxShadow, borderRadius, background, focusWidth ...)
     * يجيها طلب من الـ JS لما تغيّر الألوان / الشادو / الكورنر
     */
    public function updateUI(Request $request)
    {
        $table = $request->input('table');
        $field = $request->input('field');
        $prop  = $request->input('prop');
        $value = $request->input('value');

        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) {
            return response()->json(['error' => 'Table JSON not found'], 404);
        }

        $data = json_decode(File::get($path), true);
        foreach ($data['fields'] as &$f) {
            if (($f['name'] ?? null) === $field) {
                if (!isset($f['ui']) || !is_array($f['ui'])) {
                    $f['ui'] = [];
                }
                $f['ui'][$prop] = $value;
            }
        }

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['status' => 'ok']);
    }

    /**
     * 🔹 توليد معاينة فورم (HTML فقط) لعرضه داخل المصمم / iframe
     *  - ما يربط بأي Route حقيقي
     *  - فقط Form تجريبي action="#"
     */
    public function generateForm(string $table)
    {
        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) {
            return response()->json(['status' => 'error', 'error' => "Table definition not found for '{$table}'"], 404);
        }

        $json   = json_decode(File::get($path), true);
        $fields = $json['fields'] ?? [];

        $html = "<div class='p-6 max-w-3xl mx-auto bg-white rounded-xl shadow-md space-y-6'>";
        $html .= "<h2 class='text-2xl font-bold mb-4 text-gray-800'>" . ucfirst($table) . " Form (Preview)</h2>";
        $html .= "<form action='#' method='POST' class='space-y-4'>";

        $focusCSS = '';

        foreach ($fields as $f) {
            $name  = $f['name']  ?? null;
            if (!$name || in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            $label = ucfirst(str_replace('_', ' ', $f['label'] ?? $name));
            $input = $f['input'] ?? 'text';
            $ui    = $f['ui']    ?? [];

            // إعدادات UI افتراضية + من JSON
            $borderColor  = $ui['borderColor']  ?? '#d1d5db'; // border-gray-300
            $borderWidth  = $ui['borderWidth']  ?? '1px';
            $borderRadius = $ui['borderRadius'] ?? '0.5rem'; // rounded-lg
            $boxShadow    = $ui['boxShadow']    ?? '0 1px 2px rgba(0,0,0,0.05)';
            $bgColor      = $ui['background']   ?? '#ffffff';
            $focusColor   = $ui['focusColor']   ?? '#2563eb'; // blue-600
            $focusWidth   = $ui['focusWidth']   ?? '2px';

            $style = "border: {$borderWidth} solid {$borderColor};"
                . "border-radius: {$borderRadius};"
                . "box-shadow: {$boxShadow};"
                . "background: {$bgColor};"
                . "transition: all 0.2s ease;";

            $fieldClass = "fld_" . $name;

            $html .= "<div class='flex flex-col space-y-1'>";
            $html .= "<label for='{$name}' class='font-medium text-gray-700'>{$label}</label>";

            if ($input === 'textarea') {
                $html .= "<textarea id='{$name}' class='{$fieldClass} p-2 w-full' style='{$style}'></textarea>";
            } elseif ($input === 'select') {
                $html .= "<select id='{$name}' class='{$fieldClass} p-2 w-full' style='{$style}'><option>Select...</option></select>";
            } elseif ($input === 'switch') {
                $html .= "<label class='inline-flex items-center cursor-pointer {$fieldClass}'>"
                    . "<input type='checkbox' class='sr-only peer'>"
                    . "<div style='{$style}' class='w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-blue-500 relative after:content-[\"\"] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-5 after:w-5 after:rounded-full after:transition-all peer-checked:after:translate-x-full'></div>"
                    . "</label>";
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

        // إزالة أسهم number
        $focusCSS .= "
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            input[type=number] {
                -moz-appearance: textfield;
            }
        ";

        if ($focusCSS) {
            $html .= "<style>{$focusCSS}</style>";
        }

        $html .= "<button type='submit' class='px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700'>Save (Preview)</button>";
        $html .= "</form></div>";

        return response()->json([
            'status'  => 'ok',
            'message' => "✅ Live form preview ready!",
            'html'    => $html,
        ]);
    }

    /**
     * 🔥 توليد CRUD كامل + فورم فعلي بنفس الستايل المخصص
     * Model + Controller + index/create/edit + Migration
     */
    public function generateCrud(string $table)
    {
        $table = strtolower($table);

        $jsonPath = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($jsonPath)) {
            return response()->json([
                'status' => 'error',
                'error'  => "❌ Table definition not found for '{$table}'.",
            ], 404);
        }

        $json   = json_decode(File::get($jsonPath), true);
        $fields = $json['fields'] ?? [];

        /*
         * 1️⃣ Model
         */
        $modelName = Str::studly(Str::singular($table));
        $modelPath = app_path("Models/{$modelName}.php");

        $fillableFields = [];
        foreach ($fields as $f) {
            $name = $f['name'] ?? null;
            if (!$name || in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }
            $fillableFields[] = "'{$name}'";
        }
        $fillablePhp = implode(', ', $fillableFields);

        // علاقات من relations.json و من relation داخل الحقول
        $relationsCode = $this->buildModelRelations($table, $fields);

        $modelContent = "<?php\n\n"
            . "namespace App\\Models;\n\n"
            . "use Illuminate\\Database\\Eloquent\\Model;\n\n"
            . "class {$modelName} extends Model\n"
            . "{\n"
            . "    protected \$table = '{$table}';\n\n"
            . "    protected \$fillable = [{$fillablePhp}];\n\n"
            . $relationsCode
            . "}\n";

        if (!is_dir(dirname($modelPath))) {
            mkdir(dirname($modelPath), 0777, true);
        }
        file_put_contents($modelPath, $modelContent);

        /*
         * 2️⃣ Controller (Resource مبسط)
         */
        $controllerName = "{$modelName}Controller";
        $controllerPath = app_path("Http/Controllers/Generated/{$controllerName}.php");
        if (!is_dir(dirname($controllerPath))) {
            mkdir(dirname($controllerPath), 0777, true);
        }

        // قواعد التحقق من JSON
        $rulesBody = $this->buildValidationRulesString($fields);

        $controllerContent = "<?php\n\n"
            . "namespace App\\Http\\Controllers\\Generated;\n\n"
            . "use App\\Models\\{$modelName};\n"
            . "use App\\Http\\Controllers\\Controller;\n"
            . "use Illuminate\\Http\\Request;\n\n"
            . "class {$controllerName} extends Controller\n"
            . "{\n"
            . "    public function index()\n"
            . "    {\n"
            . "        \$data = {$modelName}::paginate(15);\n"
            . "        return view('generated.{$table}.index', compact('data'));\n"
            . "    }\n\n"
            . "    public function create()\n"
            . "    {\n"
            . "        return view('generated.{$table}.create');\n"
            . "    }\n\n"
            . "    public function store(Request \$request)\n"
            . "    {\n"
            . "        \$data = \$request->validate([\n"
            . $rulesBody
            . "        ]);\n\n"
            . "        {$modelName}::create(\$data);\n"
            . "        return redirect()->route('{$table}.index')->with('success', 'Created successfully');\n"
            . "    }\n\n"
            . "    public function edit(\$id)\n"
            . "    {\n"
            . "        \$row = {$modelName}::findOrFail(\$id);\n"
            . "        return view('generated.{$table}.edit', compact('row'));\n"
            . "    }\n\n"
            . "    public function update(Request \$request, \$id)\n"
            . "    {\n"
            . "        \$row = {$modelName}::findOrFail(\$id);\n"
            . "        \$data = \$request->validate([\n"
            . $rulesBody
            . "        ]);\n\n"
            . "        \$row->update(\$data);\n"
            . "        return redirect()->route('{$table}.index')->with('success', 'Updated successfully');\n"
            . "    }\n\n"
            . "    public function destroy(\$id)\n"
            . "    {\n"
            . "        {$modelName}::destroy(\$id);\n"
            . "        return redirect()->route('{$table}.index')->with('success', 'Deleted successfully');\n"
            . "    }\n"
            . "}\n";

        file_put_contents($controllerPath, $controllerContent);

        /*
         * 3️⃣ Views (index / create / edit) مع Tailwind الأبيض العصري + نفس الـ UI
         */
        $viewDir = resource_path("views/generated/{$table}");
        if (!is_dir($viewDir)) {
            mkdir($viewDir, 0777, true);
        }

        // 🧾 index.blade.php
        $indexView = $this->buildIndexViewBlade($table, $modelName);
        file_put_contents("{$viewDir}/index.blade.php", $indexView);

        // 🧾 create.blade.php
        $createView = $this->buildFormBlade($table, $fields, 'create');
        file_put_contents("{$viewDir}/create.blade.php", $createView);

        // 🧾 edit.blade.php
        $editView = $this->buildFormBlade($table, $fields, 'edit');
        file_put_contents("{$viewDir}/edit.blade.php", $editView);

        /*
         * 4️⃣ Migration بسيطة
         */
        $migrationName = date('Y_m_d_His') . "_create_{$table}_table.php";
        $migrationPath = database_path("migrations/{$migrationName}");

        $migration = "<?php\n\n"
            . "use Illuminate\\Database\\Migrations\\Migration;\n"
            . "use Illuminate\\Database\\Schema\\Blueprint;\n"
            . "use Illuminate\\Support\\Facades\\Schema;\n\n"
            . "return new class extends Migration\n"
            . "{\n"
            . "    public function up(): void\n"
            . "    {\n"
            . "        Schema::create('{$table}', function (Blueprint \$table) {\n"
            . "            \$table->id();\n";

        foreach ($fields as $f) {
            $name = $f['name'] ?? null;
            if (!$name || in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            // نوع بسيط فقط (ممكن تطوره حسب type)
            $migration .= "            \$table->string('{$name}')->nullable();\n";
        }

        $migration .= "            \$table->timestamps();\n"
            . "        });\n"
            . "    }\n\n"
            . "    public function down(): void\n"
            . "    {\n"
            . "        Schema::dropIfExists('{$table}');\n"
            . "    }\n"
            . "};\n";

        file_put_contents($migrationPath, $migration);

        return response()->json([
            'status'     => 'ok',
            'message'    => "✅ Full CRUD + styled form for '{$table}' generated successfully!",
            'model'      => $modelPath,
            'controller' => $controllerPath,
            'views'      => [
                $viewDir . '/index.blade.php',
                $viewDir . '/create.blade.php',
                $viewDir . '/edit.blade.php',
            ],
            'migration'  => $migrationPath,
        ]);
    }

    /* ---------------------------------------------------------------------
     * 🧩 Helpers
     * ------------------------------------------------------------------ */

    /**
     * بناء كود علاقات الـ Model من relations.json + من relation داخل الحقول
     */
    protected function buildModelRelations(string $table, array $fields): string
    {
        $methods = [];

        // من relations.json
        $relationsPath = storage_path('builder/relations.json');
        if (File::exists($relationsPath)) {
            $relations = json_decode(File::get($relationsPath), true);
            foreach ($relations as $rel) {
                $a    = $rel['table_a']       ?? null;
                $b    = $rel['table_b']       ?? null;
                $type = $rel['relation_type'] ?? null;
                if (!$a || !$b || !$type) {
                    continue;
                }

                if ($a === $table && $type === 'belongsTo') {
                    $relatedModel = Str::studly(Str::singular($b));
                    $methodName   = Str::camel(Str::singular($b));
                    $methods["belongsTo_{$methodName}"] =
                        "    public function {$methodName}()\n"
                        . "    {\n"
                        . "        return \$this->belongsTo(\\App\\Models\\{$relatedModel}::class);\n"
                        . "    }\n";
                }

                if ($a === $table && in_array($type, ['hasMany', 'hasOne'])) {
                    $relatedModel = Str::studly(Str::singular($b));
                    $relationType = $type === 'hasOne' ? 'hasOne' : 'hasMany';
                    $methodName   = $relationType === 'hasMany'
                        ? Str::camel(Str::plural($b))
                        : Str::camel(Str::singular($b));

                    $methods["{$relationType}_{$methodName}"] =
                        "    public function {$methodName}()\n"
                        . "    {\n"
                        . "        return \$this->{$relationType}(\\App\\Models\\{$relatedModel}::class);\n"
                        . "    }\n";
                }
            }
        }

        // من الحقول: "relation": "departments.name"
        foreach ($fields as $f) {
            if (empty($f['relation']) || !is_string($f['relation'])) {
                continue;
            }

            if (!str_contains($f['relation'], '.')) {
                continue;
            }

            [$relatedTable] = explode('.', $f['relation'], 2);
            $relatedModel   = Str::studly(Str::singular($relatedTable));
            $methodName     = Str::camel(Str::singular($relatedTable));

            $key = "belongsToField_{$methodName}";
            if (!isset($methods[$key])) {
                $methods[$key] =
                    "    public function {$methodName}()\n"
                    . "    {\n"
                    . "        return \$this->belongsTo(\\App\\Models\\{$relatedModel}::class);\n"
                    . "    }\n";
            }
        }

        if (empty($methods)) {
            return '';
        }

        return implode("\n\n", $methods) . "\n";
    }

    /**
     * بناء نص مصفوفة قواعد التحقق لحقول الـ Form
     */
    protected function buildValidationRulesString(array $fields): string
    {
        $lines = [];

        foreach ($fields as $f) {
            $name = $f['name'] ?? null;
            if (!$name || in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            $ruleParts = [];
            $ruleParts[] = !empty($f['required']) ? 'required' : 'nullable';

            $type = $f['type'] ?? 'string';

            switch ($type) {
                case 'integer':
                case 'bigint':
                    $ruleParts[] = 'integer';
                    break;
                case 'decimal':
                case 'float':
                case 'double':
                    $ruleParts[] = 'numeric';
                    break;
                case 'boolean':
                    $ruleParts[] = 'boolean';
                    break;
                case 'date':
                case 'datetime':
                    $ruleParts[] = 'date';
                    break;
                case 'email':
                    $ruleParts[] = 'email';
                    break;
                default:
                    $ruleParts[] = 'string';
                    break;
            }

            $rules = implode('|', $ruleParts);
            $lines[] = "            '{$name}' => '{$rules}',";
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * بناء index.blade.php منسق بـ Tailwind الأبيض العصري
     */
    protected function buildIndexViewBlade(string $table, string $modelName): string
    {
        $blade = <<<BLADE
@extends('layouts.app')

@section('content')
<div class="p-6 max-w-6xl mx-auto">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">{$modelName} List</h2>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('{$table}.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow">
            ➕ Add {$modelName}
        </a>
        <form method="GET" action="{{ route('{$table}.index') }}" class="flex gap-2">
            <input type="text" name="search" placeholder="Search..."
                   value="{{ request('search') }}"
                   class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <button type="submit"
                    class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 border">
                🔍
            </button>
        </form>
    </div>

    <div class="overflow-x-auto bg-white rounded-xl shadow">
        <table class="min-w-full border border-gray-200 divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                @php
                    \$columns = (isset(\$data) && \$data->count() > 0)
                        ? array_keys(\$data->first()->getAttributes())
                        : [];
                @endphp
                <tr>
                    @foreach(\$columns as \$col)
                        <th class="px-4 py-2 text-left text-gray-600 font-semibold capitalize">
                            {{ str_replace('_',' ', \$col) }}
                        </th>
                    @endforeach
                    <th class="px-4 py-2 text-center text-gray-600 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse(\$data as \$row)
                    <tr class="hover:bg-gray-50 transition">
                        @foreach(\$row->getAttributes() as \$value)
                            <td class="px-4 py-2 text-gray-700">{{ \$value }}</td>
                        @endforeach
                        <td class="px-4 py-2 text-center">
                            <a href="{{ route('{$table}.edit', \$row->id) }}"
                               class="text-blue-600 hover:underline mx-1">✏️</a>
                            <form method="POST" action="{{ route('{$table}.destroy', \$row->id) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-600 hover:underline mx-1"
                                        onclick="return confirm('Are you sure?')">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="100%" class="px-4 py-4 text-center text-gray-500">
                            No data available
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ \$data->links() }}
    </div>
</div>
@endsection
BLADE;

        return $blade;
    }

    /**
     * بناء create/edit.blade.php بنفس الستايل المخصص من JSON + Tailwind
     *
     * @param string $mode 'create' أو 'edit'
     */
    protected function buildFormBlade(string $table, array $fields, string $mode = 'create'): string
    {
        $isEdit = $mode === 'edit';
        $title  = $isEdit ? "Edit " . ucfirst($table) : "Create " . ucfirst($table);
        $route  = $isEdit ? "route('{$table}.update', \$row->id)" : "route('{$table}.store')";
        $methodExtra = $isEdit ? "@method('PUT')" : '';

        // CSS للـ Focus + إخفاء أسهم الأرقام
        $focusCSS = '';
        foreach ($fields as $f) {
            $name = $f['name'] ?? null;
            if (!$name || in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            $ui         = $f['ui'] ?? [];
            $focusColor = $ui['focusColor'] ?? '#2563eb';
            $focusWidth = $ui['focusWidth'] ?? '2px';
            $fieldClass = "fld_" . $name;

            $focusCSS .= ".{$fieldClass}:focus{
                outline: none !important;
                border-color: {$focusColor} !important;
                box-shadow: 0 0 0 {$focusWidth} {$focusColor}55 !important;
            }\n";
        }

        $focusCSS .= "
            input[type=number]::-webkit-inner-spin_button,
            input[type=number]::-webkit-outer-spin_button {
                -webkit-appearance: none;
                margin: 0;
            }
            input[type=number] {
                -moz-appearance: textfield;
            }
        ";

        $blade = "@extends('layouts.app')\n@section('content')\n";
        $blade .= "<div class=\"p-6 max-w-3xl mx-auto bg-white rounded-xl shadow-md space-y-6\">\n";
        $blade .= "<h2 class=\"text-2xl font-bold mb-1 text-gray-800\">{$title}</h2>\n";
        $blade .= "<p class=\"text-gray-500 text-sm mb-4\">Tailwind white modern styled form auto-generated by AutoCrudSmart.</p>\n";

        $blade .= "<style>\n{$focusCSS}\n</style>\n";

        $blade .= "<form method=\"POST\" action=\"{{ {$route} }}\" class=\"space-y-4\">\n";
        $blade .= "@csrf\n{$methodExtra}\n\n";

        foreach ($fields as $f) {
            $name = $f['name'] ?? null;
            if (!$name || in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            $label = ucfirst(str_replace('_', ' ', $f['label'] ?? $name));
            $input = $f['input'] ?? 'text';
            $ui    = $f['ui'] ?? [];

            $borderColor  = $ui['borderColor']  ?? '#d1d5db';
            $borderWidth  = $ui['borderWidth']  ?? '1px';
            $borderRadius = $ui['borderRadius'] ?? '0.5rem';
            $boxShadow    = $ui['boxShadow']    ?? '0 1px 2px rgba(0,0,0,0.05)';
            $bgColor      = $ui['background']   ?? '#ffffff';

            $style = "border: {$borderWidth} solid {$borderColor};"
                . "border-radius: {$borderRadius};"
                . "box-shadow: {$boxShadow};"
                . "background: {$bgColor};"
                . "transition: all 0.2s ease;";

            $fieldClass = "fld_" . $name;

            $blade .= "<div class='flex flex-col gap-1'>\n";
            $blade .= "<label for='{$name}' class='font-medium text-gray-700'>{$label}</label>\n";

            // value / old() helper
            $valueExpr = $isEdit
                ? "{{ old('{$name}', \$row->{$name}) }}"
                : "{{ old('{$name}') }}";

            if ($input === 'textarea') {
                $blade .= "<textarea name='{$name}' id='{$name}' class='{$fieldClass} border p-2 w-full' style='{$style}'>{$valueExpr}</textarea>\n";
            } elseif ($input === 'select') {

                // لو عنده relation مثل departments.name
                if (!empty($f['relation']) && is_string($f['relation']) && str_contains($f['relation'], '.')) {
                    [$relatedTable, $relatedColumn] = explode('.', $f['relation'], 2);
                    $relatedModel = Str::studly(Str::singular($relatedTable));

                    $blade .= "<?php \$options = App\\Models\\{$relatedModel}::all(); ?>\n";
                    $blade .= "<select name='{$name}' id='{$name}' class='{$fieldClass} border p-2 w-full' style='{$style}'>\n";
                    $blade .= "<option value=''>Select...</option>\n";
                    $blade .= "@foreach(\$options as \$opt)\n";
                    if ($isEdit) {
                        $blade .= "<option value='{{ \$opt->id }}' @selected(old('{$name}', \$row->{$name}) == \$opt->id)>{{ \$opt->{$relatedColumn} }}</option>\n";
                    } else {
                        $blade .= "<option value='{{ \$opt->id }}' @selected(old('{$name}') == \$opt->id)>{{ \$opt->{$relatedColumn} }}</option>\n";
                    }
                    $blade .= "@endforeach\n";
                    $blade .= "</select>\n";
                }
                else {
                    // محاولة ذكية لاستنتاج الجدول من اسم الحقل: department_id => departments
                    $relatedTable = Str::plural(str_replace('_id', '', $name));
                    $relatedModel = Str::studly(Str::singular($relatedTable));

                    $blade .= "<?php if (class_exists(App\\Models\\{$relatedModel}::class)) { \$options = App\\Models\\{$relatedModel}::all(); } else { \$options = collect(); } ?>\n";
                    $blade .= "<select name='{$name}' id='{$name}' class='{$fieldClass} border p-2 w-full' style='{$style}'>\n";
                    $blade .= "<option value=''>Select...</option>\n";
                    $blade .= "@foreach(\$options as \$opt)\n";
                    if ($isEdit) {
                        $blade .= "<option value='{{ \$opt->id }}' @selected(old('{$name}', \$row->{$name}) == \$opt->id)>{{ \$opt->name ?? \$opt->title ?? 'Option' }}</option>\n";
                    } else {
                        $blade .= "<option value='{{ \$opt->id }}' @selected(old('{$name}') == \$opt->id)>{{ \$opt->name ?? \$opt->title ?? 'Option' }}</option>\n";
                    }
                    $blade .= "@endforeach\n";
                    $blade .= "</select>\n";
                }

            } elseif ($input === 'switch') {
                $checkedExpr = $isEdit
                    ? "{{ old('{$name}', \$row->{$name}) ? 'checked' : '' }}"
                    : "{{ old('{$name}') ? 'checked' : '' }}";

                $blade .= "<label class='inline-flex items-center cursor-pointer'>\n";
                $blade .= "  <input type='checkbox' name='{$name}' class='sr-only peer' {$checkedExpr}>\n";
                $blade .= "  <div style='{$style}' class='w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-blue-500 relative after:content-[\"\"] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-5 after:w-5 after:rounded-full after:transition-all peer-checked:after:translate-x-full'></div>\n";
                $blade .= "</label>\n";
            } else {
                $blade .= "<input type='{$input}' name='{$name}' id='{$name}' value=\"{$valueExpr}\" class='{$fieldClass} border p-2 w-full' style='{$style}'>\n";
            }

            // رسائل الخطأ
            $blade .= "@error('{$name}')<span class='text-sm text-red-600'>{{ \$message }}</span>@enderror\n";

            $blade .= "</div>\n";
        }

        $btnText = $isEdit ? 'Update' : 'Save';

        $blade .= "<div class='pt-3 flex gap-3'>\n";
        $blade .= "  <button type='submit' class='px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow'>{$btnText}</button>\n";
        $blade .= "  <a href='{{ route(\"{$table}.index\") }}' class='px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 border'>Cancel</a>\n";
        $blade .= "</div>\n";

        $blade .= "</form>\n</div>\n@endsection";

        return $blade;
    }
}
