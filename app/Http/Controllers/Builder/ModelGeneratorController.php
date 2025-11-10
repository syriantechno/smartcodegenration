<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use App\Services\ModelGeneratorService;

class ModelGeneratorController extends Controller
{
    public function generate($table)
    {
        $service = new ModelGeneratorService();
        $result = $service->generateModel($table);
        return response()->json($result);
    }
}
