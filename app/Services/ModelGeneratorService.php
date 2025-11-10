<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelGeneratorService
{
    protected string $tablesPath;
    protected string $modelsPath;
    protected string $relationsPath;

    public function __construct()
    {
        $this->tablesPath = storage_path('builder/tables');
        $this->modelsPath = app_path('Models/Generated');
        $this->relationsPath = storage_path('builder/relations.json');

        if (!is_dir($this->modelsPath)) {
            mkdir($this->modelsPath, 0777, true);
        }
    }

    /** 🔹 إنشاء موديل واحد */
    public function generateModel(string $table): array
    {
        $path = "{$this->tablesPath}/{$table}.json";
        if (!File::exists($path)) {
            return ['error' => "JSON for table '{$table}' not found."];
        }

        $json = json_decode(File::get($path), true);
        $fields = $json['fields'] ?? [];
        $modelName = Str::studly(Str::singular($table));
        $modelFile = "{$this->modelsPath}/{$modelName}.php";

        $fillable = collect($fields)
            ->pluck('name')
            ->reject(fn($f) => in_array($f, ['id', 'created_at', 'updated_at']))
            ->map(fn($f) => "'{$f}'")
            ->implode(', ');

        $relationsCode = $this->generateRelations($table);

        $code = "<?php\n\nnamespace App\\Models\\Generated;\n\nuse Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Database\\Eloquent\\Model;\n\nclass {$modelName} extends Model\n{\n";
        $code .= "    use HasFactory;\n\n";
        $code .= "    protected \$table = '{$table}';\n";
        $code .= "    protected \$fillable = [{$fillable}];\n\n";
        $code .= $relationsCode;
        $code .= "}\n";

        File::put($modelFile, $code);

        return [
            'status' => 'ok',
            'model' => $modelName,
            'path' => $modelFile,
        ];
    }

    /** 🔹 توليد العلاقات داخل الموديل */
    private function generateRelations(string $table): string
    {
        if (!File::exists($this->relationsPath)) return '';

        $relations = json_decode(File::get($this->relationsPath), true);
        $output = "";

        foreach ($relations as $r) {
            $a = $r['table_a'];
            $b = $r['table_b'];
            $type = $r['relation_type'];
            $modelA = Str::studly(Str::singular($a));
            $modelB = Str::studly(Str::singular($b));

            if ($a === $table && $type === 'belongsTo') {
                $output .= "    public function {$b}()\n    {\n";
                $output .= "        return \$this->belongsTo({$modelB}::class, '{$b}_id');\n";
                $output .= "    }\n\n";
            } elseif ($b === $table && in_array($type, ['hasMany', 'hasOne'])) {
                $method = Str::camel(Str::plural($a));
                $output .= "    public function {$method}()\n    {\n";
                $output .= "        return \$this->{$type}({$modelA}::class, '{$a}_id');\n";
                $output .= "    }\n\n";
            }
        }

        return $output;
    }
}
