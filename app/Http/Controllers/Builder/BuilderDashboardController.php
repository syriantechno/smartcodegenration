<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class BuilderDashboardController
 * 
 * @package App\Http\Controllers\Builder
 * 
 * @desc    Controller responsible for managing the builder dashboard.
 *          Provides an overview of the application's builder features and statistics.
 */
class BuilderDashboardController extends Controller
{
    /**
     * Display the builder dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        try {
            $stats = $this->getDashboardStats();
            return view('builder.dashboard', compact('stats'));
        } catch (\Exception $e) {
            Log::error('Dashboard loading error: ' . $e->getMessage());
            return view('builder.dashboard')
                ->with('error', 'حدث خطأ أثناء تحميل لوحة التحكم');
        }
    }

    /**
     * Get dashboard statistics.
     *
     * @return array
     */
    protected function getDashboardStats(): array
    {
        return [
            'tables_count' => $this->getTablesCount(),
            'models_count' => $this->getModelsCount(),
            'migrations_count' => $this->getMigrationsCount(),
            'last_activity' => $this->getLastActivity(),
        ];
    }

    /**
     * Get the count of database tables.
     *
     * @return int
     */
    protected function getTablesCount(): int
    {
        try {
            return count(DB::select('SHOW TABLES'));
        } catch (\Exception $e) {
            Log::error('Error getting tables count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get the count of generated models.
     *
     * @return int
     */
    protected function getModelsCount(): int
    {
        try {
            $path = app_path('Models');
            if (!is_dir($path)) {
                return 0;
            }
            return count(glob($path . '/*.php'));
        } catch (\Exception $e) {
            Log::error('Error getting models count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get the count of migrations.
     *
     * @return int
     */
    protected function getMigrationsCount(): int
    {
        try {
            return count(glob(database_path('migrations/*.php')));
        } catch (\Exception $e) {
            Log::error('Error getting migrations count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get the last activity timestamp.
     *
     * @return string
     */
    protected function getLastActivity(): string
    {
        try {
            $migrationsPath = database_path('migrations');
            $files = glob($migrationsPath . '/*.php');
            
            if (empty($files)) {
                return 'لا توجد أنشطة سابقة';
            }
            
            $lastModified = filemtime($files[0]);
            foreach ($files as $file) {
                $fileModified = filemtime($file);
                if ($fileModified > $lastModified) {
                    $lastModified = $fileModified;
                }
            }
            
            return date('Y-m-d H:i:s', $lastModified);
        } catch (\Exception $e) {
            Log::error('Error getting last activity: ' . $e->getMessage());
            return 'غير متوفر';
        }
    }

    /**
     * Get system health status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function healthCheck(): JsonResponse
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            // Test storage permissions
            $storagePath = storage_path('framework/sessions');
            if (!is_writable($storagePath)) {
                throw new \RuntimeException('Storage directory is not writable');
            }
            
            return response()->json([
                'status' => 'healthy',
                'database' => 'connected',
                'storage' => 'writable',
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }
}
