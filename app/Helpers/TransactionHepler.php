<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('handleTransaction')) {
    function handleTransaction(callable $callback, string $successMessage = '', $resource = null, int $statusCode = 200, bool $inline_request = false)
    {
        $is_application = (bool) request()->query('is_application', false);

        try {
            $result = DB::transaction($callback);

            $data = $result;
            if ($resource) {
                if (is_string($resource) && class_exists($resource)) {
                    $data = $resource::make($result);
                } elseif ($resource instanceof \Illuminate\Http\Resources\Json\JsonResource) {
                    $data = $resource;
                }
            }

            $responseArray = [
                'success' => true,
                'status_code' => $statusCode,
                'message' => __($successMessage ?: 'Operation successful.'),
                'data' => $data,
            ];

            return response()->json($responseArray);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Laravel renders this as a proper 422 with per-field errors.
            // Swallowing it turned every failed validation inside a transaction
            // into the same opaque "Operation failed."
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            // abort(404) / abort(422) inside the callback said what it meant;
            // let it through instead of flattening it.
            throw $e;
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            $errorResponse = [
                'success' => false,
                'status_code' => 403,
                'message' => __('Operation failed.'),
                'error' => $e->getMessage(),
            ];

            // The status now matches what the body has always claimed. Without
            // it the transaction rolled back and the response still said HTTP
            // 200, so both apps reported success over a write that never
            // happened — the worst possible failure mode. The published app
            // handles this correctly: only 401 sends it to the sign-in screen,
            // everything else surfaces `message` in a snackbar.
            return response()->json($errorResponse, 403);
        }
    }

}
