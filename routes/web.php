<?php
use App\Http\Controllers\BuilderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/builder/tables', [BuilderController::class, 'index'])->name('builder.tables');
Route::post('/builder/tables', [BuilderController::class, 'store'])->name('builder.store');
Route::get('/builder/relations', [BuilderController::class, 'relations'])->name('builder.relations');
Route::post('/builder/relations', [BuilderController::class, 'saveRelation'])->name('builder.relations.save');
Route::get('/builder/forms', [BuilderController::class, 'forms'])->name('builder.forms');
Route::post('/builder/forms', [BuilderController::class, 'saveForm'])->name('builder.forms.save');
Route::post('/builder/apply', [BuilderController::class, 'applyToDatabase'])->name('builder.apply');
Route::get('/builder/db', [BuilderController::class, 'exploreDatabase'])->name('builder.db');
Route::get('/builder/db/{table}', [BuilderController::class, 'tableStructure'])->name('builder.db.table');
Route::post('/builder/inject/{table}', [BuilderController::class, 'injectToDatabase'])
    ->name('builder.inject');

use App\Http\Controllers\BuilderRelationsController;

Route::get('/builder/relations', [BuilderRelationsController::class, 'index'])->name('builder.relations');
Route::post('/builder/relations/save', [BuilderRelationsController::class, 'save'])->name('builder.relations.save');
Route::get('/builder/relations/inject/{index}', [BuilderRelationsController::class, 'inject'])->name('builder.relations.inject');

Route::get('/builder', [BuilderController::class, 'index'])->name('builder.index');
Route::post('/builder/save', [BuilderController::class, 'saveTable'])->name('builder.save');
Route::post('/builder/inject/{table}', [BuilderController::class, 'injectToDatabase'])->name('builder.inject');
use App\Http\Controllers\BuilderFormController;

Route::get('/builder/form', [BuilderFormController::class, 'index'])->name('builder.form');
Route::post('/builder/form/store', [BuilderFormController::class, 'store'])->name('builder.form.store');
use App\Http\Controllers\CrudGeneratorController;

Route::get('/builder/crud', [CrudGeneratorController::class, 'index'])->name('builder.crud');
Route::post('/builder/crud/generate', [CrudGeneratorController::class, 'generate'])->name('builder.crud.generate');

Route::resource('/generated/employees', App\Http\Controllers\Generated\EmployeesController::class)
    ->names('generated.employees');
Route::resource('/generated/department', App\Http\Controllers\Generated\DepartmentController::class)
    ->names('generated.department');


