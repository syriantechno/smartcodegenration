<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Builder\BaseBuilderController;
use App\Http\Controllers\Builder\BuilderRelationsController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BuilderController extends BaseBuilderController
{
    protected const TABLES_DIRECTORY = 'builder/tables'; // تمت إزالة / البادئة

    /**
     * عرض صفحة إدارة الجداول
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            // التأكد من وجود مجلد التخزين
            $this->ensureStorageDirectoryExists(self::TABLES_DIRECTORY);

            return view('builder.tables', [
                'savedTables' => $this->getSavedTables(),
                'dbTables' => $this->getDatabaseTables()
            ]);

        } catch (\Exception $e) {
            Log::error('فشل في تحميل صفحة الجداول: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل صفحة الجداول');
        }
    }

    /**
     * الحصول على الجداول المحفوظة
     *
     * @return array
     */
    protected function getSavedTables(): array
    {
        try {
            $tables = [];
            $directory = storage_path('builder/tables');

            // 1. تسجيل معلومات المسار
            Log::info('=== بدء قراءة الجداول ===');

            // التأكد من وجود المجلد
            if (!file_exists($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    Log::error('فشل في إنشاء المجلد: ' . $directory);
                    return [];
                }
            }

            Log::info('معلومات المسار:', [
                'المسار_المطلوب' => $directory,
                'المسار_الحقيقي' => realpath($directory) ?: 'غير موجود',
                'موجود' => 'نعم',
                'قابل_للقراءة' => is_readable($directory) ? 'نعم' : 'لا',
                'قابل_للكتابة' => is_writable($directory) ? 'نعم' : 'لا'
            ]);

            // 2. إنشاء المجلد إذا لم يكن موجوداً
            if (!file_exists($directory)) {
                Log::info('المجلد غير موجود، جاري إنشاءه...');
                if (!mkdir($directory, 0755, true)) {
                    $error = error_get_last();
                    Log::error('فشل في إنشاء المجلد', [
                        'المسار' => $directory,
                        'الخطأ' => $error['message'] ?? 'غير معروف',
                        'صلاحيات_المجلد_الوالد' => $this->checkPathPermissions(dirname($directory))
                    ]);
                    return [];
                }
                Log::info('تم إنشاء المجلد بنجاح: ' . $directory);
                return [];
            }

            // 3. قراءة الملفات من المجلد
            $pattern = $directory . DIRECTORY_SEPARATOR . '*.json';
            $files = glob($pattern);

            Log::info('نتيجة البحث عن الملفات:', [
                'النمط_المستخدم' => $pattern,
                'عدد_الملفات_الموجودة' => count($files),
                'الملفات' => $files
            ]);

            foreach ($files as $file) {
                $content = file_get_contents($file);
                if ($content === false) continue;

                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($data['table'])) {
                    $tables[] = [
                        'name' => $data['table'],
                        'fields' => count($data['fields'] ?? []),
                        'created_at' => $data['created_at'] ?? null,
                        'updated_at' => $data['updated_at'] ?? null,
                        'file' => basename($file)
                    ];
                }
            }

            // ترتيب الجداول حسب تاريخ التحديث
            usort($tables, function($a, $b) {
                $aTime = $a['updated_at'] ?? $a['created_at'] ?? '';
                $bTime = $b['updated_at'] ?? $b['created_at'] ?? '';
                return strtotime($bTime) - strtotime($aTime);
            });

            return $tables;

        } catch (\Exception $e) {
            Log::error('فشل في قراءة الجداول المحفوظة: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * حفظ تعريف الجدول في ملف JSON
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTable(Request $request)
    {
        try {
            $validated = $request->validate([
                'table' => 'required|string|max:255',
                'fields' => 'required|array',
                'fields.*.name' => 'required|string|max:255',
                'fields.*.type' => 'required|string|max:50',
                'fields.*.length' => 'nullable|integer|min:1',
                'fields.*.nullable' => 'boolean',
                'fields.*.default' => 'nullable|string|max:255',
                'fields.*.attributes' => 'nullable|string|max:255',
                'fields.*.index' => 'nullable|boolean',
                'fields.*.unique' => 'nullable|boolean',
                'fields.*.primary' => 'nullable|boolean',
                'fields.*.comment' => 'nullable|string|max:255',
                'timestamps' => 'boolean',
                'softDeletes' => 'boolean'
            ]);

            $tableName = $validated['table'];
            $fileName = Str::snake($tableName) . '.json';
            
            // استخدام storage_path مباشرة مع المسار الصحيح
            $directory = storage_path('builder/tables');
            $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

            // التأكد من وجود المجلد
            if (!file_exists($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new \Exception('فشل في إنشاء مجلد الجداول: ' . $directory);
                }
            }

            // تحضير بيانات الجدول للحفظ
            $tableData = [
                'table' => $tableName,
                'fields' => $validated['fields'],
                'timestamps' => $validated['timestamps'] ?? false,
                'softDeletes' => $validated['softDeletes'] ?? false,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];

            // تحويل البيانات إلى JSON
            $jsonData = json_encode($tableData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($jsonData === false) {
                throw new \Exception('فشل في ترميز بيانات JSON: ' . json_last_error_msg());
            }

            // محاولة حفظ الملف
            $result = file_put_contents($filePath, $jsonData);
            
            if ($result === false) {
                throw new \Exception('فشل في حفظ ملف الجدول: ' . $filePath);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الجدول بنجاح',
                'table' => $tableData
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('فشل في حفظ الجدول: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء محاولة حفظ الجدول',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على جداول قاعدة البيانات
     *
     * @return array
     */
    protected function getDatabaseTables(): array
    {
        try {
            $tables = [];
            $dbTables = DB::select('SHOW TABLES');

            foreach ($dbTables as $table) {
                $tableName = reset($table);
                $tables[] = [
                    'name' => $tableName,
                    'exists' => true
                ];
            }

            return $tables;

        } catch (\Exception $e) {
            Log::error('فشل في قراءة جداول قاعدة البيانات: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * إرجاع استجابة ناجحة
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function sendSuccess($data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * إرجاع استجابة خطأ
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    protected function sendError(string $message = '', int $statusCode = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message ?: 'حدث خطأ ما',
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * إرجاع استجابة غير موجود
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function sendNotFound(string $message = 'الملف المطلوب غير موجود'): JsonResponse
    {
        return $this->sendError($message, 404);
    }

    /**
     * معالجة الاستثناءات
     *
     * @param \Exception $e
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function handleException(\Exception $e, string $message = 'حدث خطأ ما', int $statusCode = 500): JsonResponse
    {
        $errorMessage = $e->getMessage();

        if (empty($errorMessage)) {
            $errorMessage = $message;
        }

        // تسجيل الخطأ في السجل
        Log::error($errorMessage, [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return $this->sendError($errorMessage, $statusCode);
    }

    // ... (جميع الدوال الأخرى تبقى كما هي)

    /**
     * حقن الجدول إلى قاعدة البيانات
     *
     * @param string $table
     * @return JsonResponse
     */
    public function injectToDatabase(string $table): JsonResponse
    {
        Log::info('1. بدء عملية حقن الجدول: ' . $table);

        try {
            // تسجيل معلومات التصحيح
            Log::info('2. معلومات التصحيح:', [
                'table' => $table,
                'storage_path' => storage_path(),
                'app_path' => app_path(),
                'base_path' => base_path(),
                'database_path' => database_path(),
                'env_DB_CONNECTION' => env('DB_CONNECTION'),
                'env_DB_DATABASE' => env('DB_DATABASE'),
                'env_DB_USERNAME' => env('DB_USERNAME'),
                'env_DB_PASSWORD' => !empty(env('DB_PASSWORD')) ? '*****' : 'فارغة',
            ]);
            // تسجيل معلومات التتبع
            Log::info('بيانات الطلب:', [
                'table' => $table,
                'method' => request()->method(),
                'headers' => request()->headers->all(),
                'input' => request()->all()
            ]);

            // التحقق من صحة اسم الجدول
            if (!$this->isValidTableName($table)) {
                $error = 'اسم الجدول غير صالح: ' . $table;
                Log::error($error);
                return $this->sendError($error, 400);
            }

            // الحصول على مسار ملف تعريف الجدول
            $schemaPath = storage_path('builder/tables/' . $table . '.json');
            Log::info('مسار ملف التعريف: ' . $schemaPath);

            if (!file_exists($schemaPath)) {
                $error = 'لم يتم العثور على ملف تعريف الجدول: ' . $schemaPath;
                Log::error($error);
                return $this->sendNotFound($error);
            }

            // قراءة ملف التعريف
            Log::info('قراءة ملف التعريف...');
            $content = file_get_contents($schemaPath);
            
            if ($content === false) {
                $error = 'فشل في قراءة ملف التعريف: ' . $schemaPath;
                Log::error($error);
                return $this->sendError($error, 500);
            }
            
            $schema = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'فشل في تحليل ملف التعريف: ' . json_last_error_msg();
                Log::error($error);
                return $this->sendError($error, 422);
            }
            
            if (empty($schema)) {
                $error = 'ملف التعريف فارغ';
                Log::error($error);
                return $this->sendError($error, 422);
            }

            if (!isset($schema['fields']) || !is_array($schema['fields'])) {
                $error = 'ملف تعريف الجدول تالف أو غير مكتمل (حقول غير موجودة)';
                Log::error($error, ['schema' => $schema]);
                return $this->sendError($error, 422);
            }

            Log::info('بدء عملية إنشاء الجدول بدون معاملات...');

            try {
                // تعطيل قيود المفاتيح الأجنبية مؤقتاً
                Schema::disableForeignKeyConstraints();

                // حذف الجدول إذا كان موجوداً
                if (Schema::hasTable($table)) {
                    Schema::dropIfExists($table);
                    Log::info('تم حذف الجدول الموجود مسبقاً: ' . $table);
                }
                // حذف الجدول إذا كان موجوداً
                if (Schema::hasTable($table)) {
                    Schema::dropIfExists($table);
                }

                // إنشاء الجدول الجديد
                Schema::create($table, function (Blueprint $table) use ($schema) {
                    $hasIdColumn = false;

                    // التحقق من وجود حقل معرف في الحقول
                    foreach ($schema['fields'] as $field) {
                        if (strtolower($field['name'] ?? '') === 'id') {
                            $hasIdColumn = true;
                            break;
                        }
                    }

                    // إضافة معرف تلقائي إذا لم يكن موجوداً
                    if (!$hasIdColumn) {
                        Log::info('إضافة عمود معرف تلقائي');
                        $table->id();
                    }

                    Log::info('إضافة الحقول إلى الجدول...');

                    // إضافة الحقول
                    foreach ($schema['fields'] as $index => $field) {
                        $name = $field['name'] ?? '';
                        $type = $field['type'] ?? 'string';
                        $required = (bool)($field['required'] ?? false);

                        // تخطي حقل المعرف إذا تمت إضافته تلقائياً
                        if (strtolower($name) === 'id' && !$hasIdColumn) {
                            Log::info("تخطي حقل المعرف (تمت إضافته تلقائياً)");
                            continue;
                        }

                        try {
                            Log::info(sprintf('إضافة العمود %s من نوع %s (مطلوب: %s)',
                                $name, $type, $required ? 'نعم' : 'لا'));

                            // إضافة العمود
                            $column = $this->addTableColumn($table, $name, $type);

                            // تعيين خصائص العمود
                            if ($required) {
                                $column->nullable(false);
                            }

                            Log::info('تمت إضافة العمود بنجاح');

                        } catch (\Exception $e) {
                            $error = sprintf('خطأ في إضافة العمود %s (النوع: %s): %s',
                                $name, $type, $e->getMessage());
                            Log::error($error);
                            throw new \Exception($error, 0, $e);
                        }
                    }

                    // إضافة حقول الطوابع الزمنية
                    Log::info('إضافة حقول الطوابع الزمنية (timestamps)');
                    $table->timestamps();

                    Log::info('اكتمل إنشاء الجدول بنجاح');
                });

                // تمكين قيود المفاتيح الأجنبية
                Schema::enableForeignKeyConstraints();

                return $this->sendSuccess(
                    ['table' => $table],
                    'تم إنشاء الجدول بنجاح في قاعدة البيانات'
                );

            } catch (\Exception $e) {
                // التأكد من تمكين قيود المفاتيح الأجنبية في حالة حدوث خطأ
                Schema::enableForeignKeyConstraints();
                throw $e;
            }

        } catch (\Illuminate\Database\QueryException $e) {
            $errorMessage = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
            Log::error($errorMessage);
            Log::error($e->getSql());
            Log::error('المعاملات: ' . json_encode($e->getBindings()));

            return $this->sendError($errorMessage, 500, [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'error_info' => $e->errorInfo ?? null
            ]);

        } catch (\Exception $e) {
            $errorMessage = 'فشل في حقن الجدول: ' . $e->getMessage();
            Log::error($errorMessage);
            Log::error($e->getTraceAsString());

            return $this->handleException($e, $errorMessage);
        }
    }

    // ... (بقية الدوال تبقى كما هي)

    /**
     * إضافة عمود إلى الجدول
     *
     * @param Blueprint $table
     * @param string $name
     * @param string $type
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    protected function addTableColumn(Blueprint $table, string $name, string $type)
    {
        $method = $this->getColumnMethod($type);
        $parameters = $this->getColumnParameters($type);

        return $table->$method($name, ...$parameters);
    }

    /**
     * الحصول على اسم الدالة المناسبة لإنشاء العمود
     *
     * @param string $type
     * @return string
     */
    protected function getColumnMethod(string $type): string
    {
        $map = [
            'string' => 'string',
            'text' => 'text',
            'integer' => 'integer',
            'bigint' => 'bigInteger',
            'float' => 'float',
            'decimal' => 'decimal',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'dateTime',
            'time' => 'time',
            'timestamp' => 'timestamp',
            'json' => 'json',
            'increments' => 'increments',
            'bigIncrements' => 'bigIncrements',
            'rememberToken' => 'rememberToken',
            'uuid' => 'uuid',
        ];

        return $map[$type] ?? 'string';
    }

    /**
     * الحصول على معاملات العمود
     *
     * @param string $type
     * @return array
     */
    /**
     * حفظ تعريف الجدول
     *
     * @param string $table
     * @param array $schema
     * @return void
     */
    protected function saveTableSchema(string $table, array $schema): void
    {
        try {
            // 1. تحديد المسارات المطلوبة
            $basePath = storage_path('builder');
            $targetDir = $basePath . DIRECTORY_SEPARATOR . 'tables';

            // 2. تسجيل معلومات المسارات
            Log::info('=== بدء حفظ الجدول ===');
            
            // 3. التأكد من وجود المجلدات
            if (!file_exists($basePath)) {
                Log::info('المجلد الأساسي غير موجود، جاري إنشائه...');
                if (!mkdir($basePath, 0755, true)) {
                    $error = error_get_last();
                    throw new \Exception(sprintf(
                        'فشل في إنشاء المجلد: %s\nالخطأ: %s',
                        $basePath,
                        $error['message'] ?? 'غير معروف'
                    ));
                }
                Log::info('تم إنشاء المجلد بنجاح: ' . $basePath);
            }

            if (!file_exists($targetDir)) {
                Log::info('المجلد الفرعي غير موجود، جاري إنشائه...');
                if (!mkdir($targetDir, 0755, true)) {
                    $error = error_get_last();
                    throw new \Exception(sprintf(
                        'فشل في إنشاء المجلد: %s\nالخطأ: %s',
                        $targetDir,
                        $error['message'] ?? 'غير معروف'
                    ));
                }
                Log::info('تم إنشاء المجلد بنجاح: ' . $targetDir);
            }

            // 4. إعداد مسار الملف النهائي
            $schemaPath = $targetDir . DIRECTORY_SEPARATOR . $table . '.json';

            // 5. تسجيل معلومات الملف والصلاحيات
            Log::info('معلومات المسارات:', [
                'base_path' => base_path(),
                'storage_path' => storage_path(),
                'target_dir' => $targetDir,
                'target_dir_exists' => file_exists($targetDir) ? 'نعم' : 'لا',
                'target_dir_writable' => is_writable($targetDir) ? 'نعم' : 'لا',
                'parent_dir' => dirname($targetDir),
                'parent_writable' => is_writable(dirname($targetDir)) ? 'نعم' : 'لا',
                'final_path' => $schemaPath,
                'final_path_writable' => is_writable(dirname($schemaPath)) ? 'نعم' : 'لا'
            ]);

            // 6. التحقق من إمكانية الكتابة في المجلد
            if (!is_writable($targetDir)) {
                $perms = $this->checkPathPermissions($targetDir);
                throw new \Exception(sprintf(
                    'لا يمكن الكتابة في المجلد: %s\nالصلاحيات: %s',
                    $targetDir,
                    json_encode($perms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ));
            }

            // 7. ترميز البيانات بصيغة JSON
            $jsonData = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($jsonData === false) {
                throw new \Exception('فشل في ترميز بيانات JSON: ' . json_last_error_msg());
            }

            // 8. محاولة حفظ الملف
            $result = file_put_contents($schemaPath, $jsonData);

            if ($result === false) {
                $error = error_get_last();
                throw new \Exception(sprintf(
                    "فشل في حفظ الملف: %s\n" .
                    "الخطأ: %s\n" .
                    "تفاصيل الصلاحيات: %s\n" .
                    "المسار الحقيقي: %s",
                    $schemaPath,
                    $error['message'] ?? 'غير معروف',
                    json_encode($this->checkPathPermissions(dirname($schemaPath)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    realpath(dirname($schemaPath)) ?: 'غير موجود'
                ));
            }

            Log::info('تم حفظ ملف التعريف بنجاح:', [
                'path' => $schemaPath,
                'permissions' => $this->checkPathPermissions($schemaPath)
            ]);

        } catch (\Exception $e) {
            Log::error('تفاصيل الخطأ في حفظ تعريف الجدول:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'directory_permissions' => isset($directory) ? $this->checkPathPermissions($directory) : 'غير متاح',
                'schema_path' => $schemaPath ?? 'غير محدد',
                'schema_dir_permissions' => isset($schemaDir) ? $this->checkPathPermissions($schemaDir) : 'غير محدد'
            ]);
            throw $e;
        }
    }

    /**
     * الحصول على معلمات العمود حسب النوع
     *
     * @param string $type
     * @return array
     */
    /**
     * التحقق من إمكانية الكتابة في المسار
     *
     * @param string $path
     * @return array
     */
    /**
     * إنشاء مجلد إذا لم يكن موجوداً
     */
    protected function ensureDirectoryExists(string $path): bool
    {
        if (!file_exists($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }

    protected function checkPathPermissions(string $path): array
    {
        $result = [
            'exists' => file_exists($path),
            'is_dir' => is_dir($path),
            'is_writable' => is_writable($path),
            'permissions' => file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : '0000',
            'owner' => 'System',
            'group' => 'Users',
            'is_windows' => true
        ];

        // محاولة الحصول على معلومات المالك إذا كان النظام يدعم ذلك
        if (function_exists('posix_getpwuid') && file_exists($path)) {
            $ownerInfo = @posix_getpwuid(fileowner($path));
            if ($ownerInfo !== false && isset($ownerInfo['name'])) {
                $result['owner'] = $ownerInfo['name'];
            }
        }

        // محاولة الحصول على معلومات المجموعة إذا كان النظام يدعم ذلك
        if (function_exists('posix_getgrgid') && file_exists($path)) {
            $groupInfo = @posix_getgrgid(filegroup($path));
            if ($groupInfo !== false && isset($groupInfo['name'])) {
                $result['group'] = $groupInfo['name'];
            }
        }

        return $result;
    }

    /**
     * الحصول على معلمات العمود حسب النوع
     *
     * @param string $type
     * @return array
     */
    protected function getColumnParameters(string $type): array
    {
        switch ($type) {
            case 'integer':
            case 'bigint':
            case 'boolean':
            case 'date':
            case 'datetime':
            case 'time':
            case 'timestamp':
            case 'json':
            case 'increments':
            case 'bigIncrements':
                return [];
            default:
                return []; // إرجاع مصفوفة فارغة كقيمة افتراضية
        }
        }

}
