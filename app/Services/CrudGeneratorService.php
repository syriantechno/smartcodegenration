<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CrudGeneratorService
{
    protected string $tablesPath;
    protected string $controllersPath;
    
    /**
     * Generate complete CRUD for a table
     *
     * @param string $table
     * @return array
     */
    public function generate(string $table): array
    {
        try {
            // Generate controller
            $controllerResult = $this->generateController($table);
            if (isset($controllerResult['error'])) {
                throw new \Exception($controllerResult['error']);
            }
            
            // Generate views
            $viewsResult = [
                'index' => $this->generateIndexView($table),
                'form' => $this->generateFormView($table)
            ];
            
            // Generate routes
            $routesAdded = $this->addRoutes($table);
            
            return [
                'success' => true,
                'controller' => $controllerResult,
                'views' => $viewsResult,
                'routes_added' => $routesAdded,
                'message' => 'تم إنشاء واجهة CRUD بنجاح'
            ];
            
        } catch (\Exception $e) {
            \Log::error('CRUD Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'فشل إنشاء واجهة CRUD: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Add routes for the generated CRUD
     *
     * @param string $table
     * @return bool
     */
    protected function addRoutes(string $table): bool
    {
        try {
            $routeFile = base_path('routes/generated.php');
            
            // Create the routes file if it doesn't exist
            if (!file_exists($routeFile)) {
                file_put_contents($routeFile, "<?php\n\n");
            }
            
            $controllerName = Str::studly(Str::singular($table)) . 'Controller';
            $routeContent = "\n// Routes for {$table}\n";
            $routeContent .= "Route::resource('{$table}', \\App\\Http\\Controllers\\Generated\\{$controllerName}');\n";
            
            // Append the routes to the file
            file_put_contents($routeFile, $routeContent, FILE_APPEND);
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Route generation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function __construct()
    {
        $this->tablesPath = storage_path('builder/tables');
        $this->controllersPath = app_path('Http/Controllers/Generated');
        if (!is_dir($this->controllersPath)) {
            mkdir($this->controllersPath, 0777, true);
        }
    }

    /** 🔹 توليد كنترولر واحد */
    public function generateController(string $table): array
    {
        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) {
            return ['error' => "JSON for table '{$table}' not found."];
        }

        $json = json_decode(File::get($path), true);
        $fields = $json['fields'] ?? [];
        $modelName = Str::studly(Str::singular($table));
        $controllerName = "{$modelName}Controller";
        $controllerFile = "{$this->controllersPath}/{$controllerName}.php";

        $fillable = collect($fields)
            ->pluck('name')
            ->reject(fn($f) => in_array($f, ['id', 'created_at', 'updated_at']))
            ->map(fn($f) => "'{$f}'")
            ->implode(', ');

        // 🔹 بناء الكنترولر
        $code = "<?php\n\n";
        $code .= "namespace App\\Http\\Controllers\\Generated;\n\n";
        $code .= "use App\\Http\\Controllers\\Controller;\n";
        $code .= "use Illuminate\\Http\\Request;\n";
        $code .= "use Illuminate\\Support\\Facades\\DB;\n\n";
        $code .= "class {$controllerName} extends Controller\n{\n";
        $code .= "    protected string \$table = '{$table}';\n\n";
        $code .= "    public function index()\n    {\n";
        $code .= "        \$records = DB::table(\$this->table)->paginate(10);\n";
        $code .= "        return view('generated.{$table}_index', compact('records'));\n";
        $code .= "    }\n\n";
        $code .= "    public function create()\n    {\n";
        $code .= "        return view('generated.{$table}_form');\n";
        $code .= "    }\n\n";
        $code .= "    public function store(Request \$request)\n    {\n";
        $code .= "        DB::table(\$this->table)->insert(\$request->except('_token'));\n";
        $code .= "        return redirect()->route('{$table}.index')->with('success', 'Created successfully');\n";
        $code .= "    }\n\n";
        $code .= "    public function edit(\$id)\n    {\n";
        $code .= "        \$item = DB::table(\$this->table)->find(\$id);\n";
        $code .= "        return view('generated.{$table}_form', compact('item'));\n";
        $code .= "    }\n\n";
        $code .= "    public function update(Request \$request, \$id)\n    {\n";
        $code .= "        DB::table(\$this->table)->where('id', \$id)->update(\$request->except('_token'));\n";
        $code .= "        return redirect()->route('{$table}.index')->with('success', 'Updated successfully');\n";
        $code .= "    }\n\n";
        $code .= "    public function destroy(\$id)\n    {\n";
        $code .= "        DB::table(\$this->table)->where('id', \$id)->delete();\n";
        $code .= "        return redirect()->back()->with('success', 'Deleted');\n";
        $code .= "    }\n";
        $code .= "}\n";

        File::put($controllerFile, $code);

        return [
            'status' => 'ok',
            'controller' => $controllerName,
            'path' => $controllerFile,
        ];
    }
    /** 🔹 توليد صفحة index لعرض السجلات */
    public function generateIndexView(string $table): array
    {
        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) {
            return ['error' => "JSON for table '{$table}' not found."];
        }

        $json = json_decode(File::get($path), true);
        $fields = $json['fields'] ?? [];
        $columns = collect($fields)
            ->pluck('name')
            ->reject(fn($f) => in_array($f, ['id', 'created_at', 'updated_at']))
            ->values()
            ->all();

        $blade = "@extends('layouts.app')\n@section('content')\n";
        $blade .= "<div class=\"p-6\">\n";
        $blade .= "<h2 class=\"text-xl font-bold mb-4\">All " . ucfirst($table) . "</h2>\n";
        $blade .= "<a href=\"{{ route('{$table}.create') }}\" class=\"btn btn-primary mb-3\">+ Add New</a>\n";
        $blade .= "<table class=\"table table-bordered w-full\">\n<thead>\n<tr>\n";

        foreach ($columns as $col) {
            $blade .= "<th>" . ucfirst(str_replace('_', ' ', $col)) . "</th>\n";
        }

        $blade .= "<th>Actions</th>\n</tr>\n</thead>\n<tbody>\n";
        $blade .= "@foreach(\$records as \$r)\n<tr>\n";
        foreach ($columns as $col) {
            $blade .= "<td>{{ \$r->{$col} }}</td>\n";
        }
        $blade .= "<td>\n";
        $blade .= "<a href=\"{{ route('{$table}.edit', \$r->id) }}\" class=\"btn btn-sm btn-warning\">Edit</a>\n";
        $blade .= "<form action=\"{{ route('{$table}.destroy', \$r->id) }}\" method=\"POST\" style=\"display:inline-block;\">\n";
        $blade .= "@csrf\n@method('DELETE')\n";
        $blade .= "<button class=\"btn btn-sm btn-danger\" onclick=\"return confirm('Delete this record?')\">Delete</button>\n";
        $blade .= "</form>\n</td>\n</tr>\n@endforeach\n";
        $blade .= "</tbody>\n</table>\n</div>\n@endsection";

        $output = resource_path("views/generated/{$table}_index.blade.php");
        File::put($output, $blade);

        return [
            'status' => 'ok',
            'message' => "✅ Index view for '{$table}' generated successfully!",
            'path' => $output
        ];
    }
    /** 🔹 توليد صفحة form تلقائيًا مع دعم العلاقات */
    public function generateFormView(string $table): array
    {
        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) {
            return ['error' => "JSON for table '{$table}' not found."];
        }

        $json = json_decode(File::get($path), true);
        $fields = $json['fields'] ?? [];

        // 📦 بناء محتوى الصفحة
        $blade = "@extends('layouts.app')\n@section('content')\n";
        $blade .= "<div class='p-6'>\n";
        $blade .= "<h2 class='text-xl font-bold mb-4'>Create " . ucfirst($table) . "</h2>\n";
        $blade .= "<form method='POST' action='{{ isset(\$item) ? route('{$table}.update', \$item->id) : route('{$table}.store') }}'>\n";
        $blade .= "@csrf\n";
        $blade .= "@if(isset(\$item)) @method('PUT') @endif\n\n";

        foreach ($fields as $f) {
            $name = $f['name'];
            if (in_array($name, ['id', 'created_at', 'updated_at'])) continue;

            $label = ucfirst(str_replace('_', ' ', $f['label'] ?? $name));
            $input = $f['input'] ?? 'text';
            $relation = $f['relation'] ?? null;

            $blade .= "<div class='mb-4'>\n";
            $blade .= "<label class='block font-medium mb-1'>{$label}</label>\n";

            // 🔸 التعامل مع select المرتبط بعلاقة
            if ($input === 'select' && $relation && str_contains($relation, '.')) {
                [$relatedTable, $relatedColumn] = explode('.', $relation);
                $blade .= "<?php \$options = []; if(\\Illuminate\\Support\\Facades\\DB::getSchemaBuilder()->hasTable('{$relatedTable}')) { ";
                $blade .= "\$col = '{$relatedColumn}'; ";
                $blade .= "\$nameCol = collect(\\Illuminate\\Support\\Facades\\DB::getSchemaBuilder()->getColumnListing('{$relatedTable}'))->first(fn(\$c) => in_array(\$c, ['name','title','code'])) ?? 'id'; ";
                $blade .= "\$options = \\Illuminate\\Support\\Facades\\DB::table('{$relatedTable}')->pluck(\$nameCol, 'id')->toArray(); } ?>\n";

                $blade .= "<select name='{$name}' class='form-control border-gray-400 bg-white'>\n";
                $blade .= "@foreach(\$options as \$key => \$value)\n";
                $blade .= "<option value='{{ \$key }}' {{ (isset(\$item) && \$item->{$name} == \$key) ? 'selected' : '' }}>{{ \$value }}</option>\n";
                $blade .= "@endforeach\n";
                $blade .= "</select>\n";

            } elseif ($input === 'textarea') {
                $blade .= "<textarea name='{$name}' class='form-control border-gray-400 bg-white'>{{ \$item->{$name} ?? '' }}</textarea>\n";
            } elseif ($input === 'switch') {
                $blade .= "<input type='checkbox' name='{$name}' value='1' {{ isset(\$item) && \$item->{$name} ? 'checked' : '' }}>\n";
            } else {
                $blade .= "<input type='{$input}' name='{$name}' class='form-control border-gray-400 bg-white' value='{{ \$item->{$name} ?? '' }}'>\n";
            }

            $blade .= "</div>\n";
        }

        $blade .= "<button type='submit' class='btn btn-primary px-4 py-2'>Save</button>\n";
        $blade .= "</form>\n</div>\n@endsection";

        $output = resource_path("views/generated/{$table}_form.blade.php");
        File::put($output, $blade);

        return [
            'status' => 'ok',
            'message' => "✅ Form view for '{$table}' generated successfully!",
            'path' => $output
        ];
    }

}
