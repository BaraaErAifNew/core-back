<?php

namespace ApiCore\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;

trait HasResponseMacro
{

    public static function responseMacro()
    {
        Response::macro('api', function (
            bool   $status,
            string $message,
                   $data = [],
                   $extra = [],
            int    $errorCode = 0,
            int    $statusCode = 200
        ): \Illuminate\Http\JsonResponse {
            $payload = [
                'status' => $status,
                'message' => $message,
                'error_code' => $errorCode,
            ];

            // Handle pagination specifically
            if ($data instanceof LengthAwarePaginator) {
                $payload = array_merge(
                    $payload,
                    [
                        'data' => $data->getCollection(),
                        'paginator' => array_diff_key($data->toArray(), ['data' => null])
                    ]
                );
            } else {
                $payload['data'] = $data;
            }

            // Add extra data if provided
            if (!empty($extra)) {
                $payload['extra'] = $extra;
            }

            return Response::json(
                $payload,
                $statusCode
            );
        });
    }

}
