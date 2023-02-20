<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const BAD_REQUEST_ERROR = 'Something went wrong';

    /**
     * @param $data
     * @param string $message
     * @return JsonResponse
     */
    public function return_success($data, $message = ''): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $data, 'message' => $message], 200);
    }

    /**
     * @param $data
     * @param int $statusCode
     * @return JsonResponse
     */
    public function return_error($data, $statusCode = 400): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $data], $statusCode);
    }

}

