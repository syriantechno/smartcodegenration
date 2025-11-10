<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Builder\BuilderController;
use App\Http\Controllers\Builder\BuilderDashboardController;
use App\Http\Controllers\Builder\BuilderFormController;
use App\Http\Controllers\Builder\BuilderPreviewController;
use App\Http\Controllers\Builder\BuilderRelationsController;
use App\Http\Controllers\Builder\CrudGeneratorController;
use App\Http\Controllers\Builder\FormPreviewController;
use App\Http\Controllers\Builder\ModelGeneratorController;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Generated\DepartmentsController;

// 🏠 الصفحة الرئيسية (افتراضية)
Route::get('/', fn() => view('welcome'));

/*
|--------------------------------------------------------------------------
| 🧩 AutoCrudSmart Routes
|--------------------------------------------------------------------------
*/
Route::prefix('builder')->group(function () {

    // 🧭 Dashboard
    Route::get('/', [BuilderDashboardController::class, 'index'])->name('builder.dashboard');

    // 🧱 Tables Manager
    Route::get('/tables', [BuilderController::class, 'index'])->name('builder.tables');
    Route::post('/tables/save', [BuilderController::class, 'saveTable'])->name('builder.tables.save');
    Route::post('/inject/{table}', [BuilderController::class, 'injectToDatabase'])->name('builder.tables.inject');

    // 🔗 Relations Manager
    Route::get('/relations', [BuilderRelationsController::class, 'index'])->name('builder.relations');
    Route::post('/relations/save', [BuilderRelationsController::class, 'save'])->name('builder.relations.save');
    Route::get('/relations/inject/{index}', [BuilderRelationsController::class, 'inject'])->name('builder.relations.inject');

    // 🎨 Form Master (الصفحة الجديدة مع اختيار الجدول + المعاينة)
    Route::get('/form-master', [FormPreviewController::class, 'master'])->name('builder.form.master');
    Route::post('/form-master/load', [FormPreviewController::class, 'loadTableFields'])->name('builder.form.load');
    Route::post('/form-master/save', [FormPreviewController::class, 'saveDesign'])->name('builder.form.save');
    Route::get('/preview/generate/{table}', [\App\Http\Controllers\Builder\BuilderPreviewController::class, 'generateForm'])
        ->name('builder.preview.generate');
    Route::get('/preview/view/{table}', [\App\Http\Controllers\Builder\BuilderPreviewController::class, 'viewGenerated'])
        ->name('builder.preview.view');
    Route::post('/preview/update-ui', [\App\Http\Controllers\Builder\BuilderPreviewController::class, 'updateUI'])
        ->name('builder.preview.updateUI');

    Route::get('/crud/generate/{table}', [BuilderPreviewController::class, 'generateCrud'])
        ->name('builder.crud.generate');







    // 🎨 Form Designer (النسخة القديمة لجدول واحد)
    Route::get('/form-designer/{table}', [FormPreviewController::class, 'index'])->name('builder.form.designer');

    // ⚙️ CRUD Generator
    Route::get('/crud', [CrudGeneratorController::class, 'index'])->name('builder.crud');
    Route::post('/crud/generate', [CrudGeneratorController::class, 'generate'])->name('builder.crud.generate');
    Route::get('/generate-controller/{table}', [CrudGeneratorController::class, 'generate'])->name('builder.generate.controller');
    Route::get('/generate-index/{table}', [CrudGeneratorController::class, 'generateIndex'])->name('builder.generate.index');

    // 🧱 Model Generator
    Route::get('/generate-model/{table}', [ModelGeneratorController::class, 'generate'])->name('builder.generate.model');

    // 📂 Output preview
    Route::get('/output', function () {
        $files = glob(resource_path('views/generated/*.blade.php'));
        $views = collect($files)->map(fn($f) => basename($f));
        return view('builder.output', compact('views'));
    })->name('builder.output');

    // 📄 عرض محتوى ملف مولّد (AJAX)
    Route::get('/output/view/{filename}', function ($filename) {
        $path = resource_path('views/generated/' . $filename);
        if (!File::exists($path)) {
            return response()->json(['error' => 'File not found']);
        }
        $content = File::get($path);
        return response()->json(['content' => $content]);
    });
});

/*
|--------------------------------------------------------------------------
| 🧮 Auto-register generated controllers (Dynamic Routes)
|--------------------------------------------------------------------------
*/
$generatedControllers = glob(app_path('Http/Controllers/Generated/*.php'));
foreach ($generatedControllers as $controllerPath) {
    $className = pathinfo($controllerPath, PATHINFO_FILENAME);
    $table = strtolower(str_replace('Controller', '', $className));
    $controller = "App\\Http\\Controllers\\Generated\\{$className}";
    if (class_exists($controller)) {
        Route::resource($table, $controller);
    }
}
Route::resource('departments', DepartmentsController::class);

