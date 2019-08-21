<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    /**
     * @param       $message
     * @param int   $status_code
     * @param array $data
     * @param array $headers
     * @param int   $options
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function response($message, $status_code = 200, $data = [],
        $headers = [], $options = 0
    ) {

        return response()->json([
                                    'message' => $message,
                                    'status_code' => $status_code,
                                    'data' => $data
                                ], $status_code, $headers, $options);
    }


    /**
     * 主要针对前端webuploader插件不能识别http错误码
     *
     * @param       $message
     * @param int   $status_code
     * @param array $data
     * @param array $headers
     * @param int   $options
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function uploadResponse($message, $status_code = 200, $data = [],
        $headers = [], $options = 0
    ) {
        $code = $status_code == 201 ?
            201 :
            200;

        return response()->json([
                                    'message' => $message,
                                    'status_code' => $status_code,
                                    'data' => $data
                                ], $code, $headers, $options);
    }

    /**
     * 主要针对异常返回
     *
     * @param       $message
     * @param int   $status_code
     * @param array $data
     * @param array $headers
     * @param int   $options
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function eResponse($message, $status_code = 200, $data = [],
        $headers = [], $options = 0
    ) {
        $code = ($status_code >= 200 && $status_code < 300) ?
            $status_code :
            200;

        return response()->json([
                                    'message' => $message,
                                    'status_code' => $status_code,
                                    'data' => $data
                                ], $code, $headers, $options);
    }

}
