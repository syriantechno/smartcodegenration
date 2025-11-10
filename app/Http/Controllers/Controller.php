<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="LaraBuilder API",
 *     version="1.0.0",
 *     description="API documentation for LaraBuilder application"
 * )
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Send a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function sendSuccess($data = null, string $message = '', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    protected function sendError(
        string $message = '', 
        int $statusCode = 400, 
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Send a not found response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function sendNotFound(string $message = 'المورد المطلوب غير موجود'): JsonResponse
    {
        return $this->sendError($message, 404);
    }

    /**
     * Send a validation error response.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return JsonResponse
     */
    protected function sendValidationError($validator): JsonResponse
    {
        return $this->sendError(
            'خطأ في التحقق من صحة البيانات', 
            422, 
            $validator->errors()->toArray()
        );
    }

    /**
     * Handle exceptions in a consistent way.
     *
     * @param \Exception $e
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function handleException(
        \Exception $e, 
        string $message = 'حدث خطأ غير متوقع', 
        int $statusCode = 500
    ): JsonResponse {
        Log::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if (config('app.debug')) {
            return $this->sendError(
                $message . ': ' . $e->getMessage(),
                $statusCode,
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
        }

        return $this->sendError($message, $statusCode);
    }
}
