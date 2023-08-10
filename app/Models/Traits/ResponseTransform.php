<?php

namespace App\Models\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use MifxPackage\Exceptions\ServiceErrorException;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom Response
 */
trait ResponseTransform
{
    /**
     * response
     *
     * @param mixed $data
     * @param mixed $message
     * @param string $status
     * @param int $code
     * @param array $headers
     * @param array $additionals
     *
     * @return JsonResponse
     */
    public function response($data = null, int $code = 200, array $additionals = [], bool $wrap = true): JsonResponse
    {
        $response = [];
        if($wrap){
            $response['data'] = $data;
        }else{
            $response = $data;
        }

        if (!empty($additionals)) {
            $response = array_merge($additionals, $response);
        }

        return response()->json($response, $code);
    }

    /**
     * Undocumented function
     *
     * @param string $message
     * @param int $code
     * @param mixed $data
     * @param array $headers
     *
     * @return JsonResponse
     */
    public function responseError($message, $code = 422, $data = null, $headers = [], $trace = null): JsonResponse
    {
        if (is_null($message)) {
            $message = __('response.message.error');
        } elseif (is_string($message)) {
            $message = __($message);
        }

        $response['status'] = 'error';
        $response['message'] = $message;

        if ($data === null) {
            $data = $message;
        }

        if (config('app.debug') && $trace !== null) {
            $response['trace'] = $trace;
        }

        if (!$code) {
            $code = 422;
        }

        if ($code === 422) {
            if (is_array($data) || is_object($data)) {
                $response['errors'] = $data;
            } else {
                $response['errors'] = [
                    'error' => [
                        $data,
                    ],
                ];
            }
        }

        return response()->json($response, $code, $headers);
    }

    /**
     * @param $resource
     * @param LengthAwarePaginator $data
     * @param int $code
     * @param array $additionals
     * @return JsonResponse
     */
    public function responsePaginate(
        $resource,
        $data,
        array $additionals = [],
    ): JsonResponse
    {
        return $this->response(
            data: $resource::collection($data->getCollection())
                ->additional(
                    collect($data->toArray())->except('data')->toArray()
                )->response()->getData(true),
            code: 200,
            additionals: $additionals,
            wrap: false
        );
    }
}
