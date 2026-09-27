<?php


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('responseHelper')) {
    function responseHelper(
        ?string $message = null,
        int     $statusCode = 200,
        mixed   $resource = null,
        bool    $inlineRequest = false
    )
    {
        $isApplication = $inlineRequest ? false : (bool)request()->query('is_application', false);
        $code = $statusCode;

        if ($isApplication) {
            return response()->json(
                !empty($resource) ? $resource : ($message ? __($message) : ''),
                $code
            );
        }

        return response()->json([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $code,
            'message' => $message ? __($message) : '',
            'data' => $resource,
        ], $code);
    }

}
