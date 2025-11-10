<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Builder;
use App\Http\Controllers\Controller;
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

    /** عرض واجهة إدارة العلاقات */
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

    /** حفظ علاقة جديدة */
    public function save(Request $request)
    {
        $data = [
            'table_a'       => $request->input('table_a'),
            'relation_type' => $request->input('relation_type'),
            'table_b'       => $request->input('table_b'),
        ];

        // ✅ حفظ العلاقة في relations.json
        if (!is_dir(dirname($this->relationsPath))) {
            mkdir(dirname($this->relationsPath), 0777, true);
        }

        $relations = File::exists($this->relationsPath)
            ? json_decode(File::get($this->relationsPath), true)
            : [];

        $relations[] = $data;
        File::put($this->relationsPath, json_encode($relations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // ✅ تحديث ملفات الجداول JSON
        $this->updateRelatedJsonFiles($data['table_a'], $data['table_b'], $data['relation_type']);

        // ✅ إنشاء الجداول فعليًا أو تعديلها في قاعدة البيانات
        $builder = new Builder\BuilderController();
        $builder->injectToDatabase($data['table_a']);
        $builder->injectToDatabase($data['table_b']);

        return response()->json([
            'status' => 'ok',
            'message' => "تم حفظ العلاقة وتحديث قاعدة البيانات بنجاح.",
            'relation' => $data
        ]);
    }


    /** 🔁 تحديث ملفات JSON للجداول بعد إنشاء علاقة جديدة */
    private function updateRelatedJsonFiles(string $tableA, string $tableB, string $type): void
    {
        $tablesPath = storage_path('builder/tables');

        // 🔸 Helper لتحميل ملف JSON موجود
        $load = function ($table) use ($tablesPath) {
            $path = "{$tablesPath}/{$table}.json";
            if (!File::exists($path)) return null;
            return json_decode(File::get($path), true);
        };

        // 🔸 Helper لحفظ الملف
        $save = function ($table, $data) use ($tablesPath) {
            $path = "{$tablesPath}/{$table}.json";
            File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        };

        // 🧠 تحديث حسب نوع العلاقة
        if ($type === 'belongsTo') {
            $dataA = $load($tableA);
            if ($dataA) {
                $found = false;
                foreach ($dataA['fields'] as $f) {
                    if ($f['name'] === "{$tableB}_id") {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $dataA['fields'][] = [
                        'name' => "{$tableB}_id",
                        'type' => 'integer',
                        'label' => ucfirst($tableB),
                        'input' => 'select',
                        'required' => false,
                        'in_table' => true,
                        'relation' => "{$tableB}.name"
                    ];
                    $save($tableA, $dataA);
                }
            }
        } elseif (in_array($type, ['hasMany', 'hasOne'])) {
            $dataB = $load($tableB);
            if ($dataB) {
                $found = false;
                foreach ($dataB['fields'] as $f) {
                    if ($f['name'] === "{$tableA}_id") {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $dataB['fields'][] = [
                        'name' => "{$tableA}_id",
                        'type' => 'integer',
                        'label' => ucfirst($tableA),
                        'input' => 'select',
                        'required' => false,
                        'in_table' => true,
                        'relation' => "{$tableA}.name"
                    ];
                    $save($tableB, $dataB);
                }
            }
        }
    }


    /** تطبيق العلاقة فعلياً على قاعدة البيانات */
    public function inject($index)
    {
        // ✅ تأكد أن مجلد builder موجود
        if (!is_dir(storage_path('builder'))) {
            mkdir(storage_path('builder'), 0777, true);
        }

        $path = storage_path('builder/relations.json');


        if (!file_exists($path)) {
            return response()->json(['error' => 'Relations file not found.']);
        }

        $relations = json_decode(file_get_contents($path), true);
        if (!isset($relations[$index])) {
            return response()->json(['error' => 'Relation not found.']);
        }

        $r = $relations[$index];
        $tableA = $r['table_a'];
        $tableB = $r['table_b'];
        $type   = $r['relation_type'];

        // ⚙️ إذا كانت العلاقة belongsTo أضف المفتاح الأجنبي
        if ($type === 'belongsTo') {
            $fk = rtrim($tableB, 's') . '_id';

            \Schema::table($tableA, function ($table) use ($fk) {
                if (!\Schema::hasColumn($table->getTable(), $fk)) {
                    $table->unsignedBigInteger($fk)->nullable()->after('id');
                }
            });

            return response()->json([
                'status' => 'ok',
                'message' => "✅ Relation injected successfully! Added '$fk' to '$tableA'."
            ]);
        }

        return response()->json(['status' => 'ok', 'message' => 'Relation type not handled yet.']);
    }



    /** 🔍 إرجاع جميع العلاقات بشكل منظم ليستفيد منها BuilderController */
    public static function getAllRelations(): array
    {
        $path = storage_path('builder/relations.json');
        if (!File::exists($path)) {
            return [];
        }

        $relations = json_decode(File::get($path), true);
        $map = [];

        foreach ($relations as $r) {
            $a = $r['table_a'];
            $b = $r['table_b'];
            $type = $r['relation_type'];

            if ($type === 'belongsTo') {
                // Table A يحتوي مفتاحًا خارجيًا يشير إلى Table B
                $map[$a]["{$b}_id"] = "{$b}.id";
            } elseif (in_array($type, ['hasMany', 'hasOne'])) {
                // Table B يحتوي مفتاحًا خارجيًا يشير إلى Table A
                $map[$b]["{$a}_id"] = "{$a}.id";
            }
        }

        return $map;
    }
}
