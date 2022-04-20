<?php

namespace Litermi\Response\Traits;

use Litermi\ErrorNotification\Services\GetInfoFromExceptionService;
use Litermi\Logs\Services\SendLogUserRequestResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 *
 */
trait ResponseTrait
{
    public function successResponseWithMessage($data = [], $message = "", $code = 200, $encodeArray = false)
    {
        $response[ 'code' ]    = $code;
        $response[ 'message' ] = $message;
        if ($encodeArray === true) {
            $data = $this->encode_array($data);
        }
        $response[ 'data' ] = $data;

        $headers = [
            'content-type'  => 'application/json',
            'cache-control' => 'no-cache',
        ];
        SendLogUserRequestResponseService::execute($data);

        return response()->json($response, $code, $headers);
    }

    private function encode_array($data)
    {
        $data = is_array($data) ? $data : $data->toArray();
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $data[ $key ] = $this->encode_array($value);
            } elseif (is_string($value)) {
                $value        = utf8_encode($value);
                $data[ $key ] = html_entity_decode($value);
            }
        }

        return $data;
    }

    public function errorExternalRequestMessage($message, $code)
    {
        return response($message, $code)->header('Content-Type', 'application/json');
    }

    public function errorCatchResponse(
        $exception,
        $message = '',
        $code = Response::HTTP_SERVICE_UNAVAILABLE
    ) {
        $exceptionCode = $exception->getCode();
        if ($exceptionCode === Response::HTTP_UNPROCESSABLE_ENTITY) {
            $message = $exception->getMessage();
            $code    = Response::HTTP_UNPROCESSABLE_ENTITY;
        }
        if ($exception instanceof ModelNotFoundException) {
            $message = $exception->getMessage();
            $code    = Response::HTTP_NOT_FOUND;
        }
        $infoException = GetInfoFromExceptionService::execute($exception);
        SendLogUserRequestResponseService::execute($infoException);
        if (config('app.debug') === true) {
            return $this->errorResponseWithMessage($infoException, $message, $code);
        }

        return $this->errorResponseWithMessage([], $message, $code);

    }

    private function errorResponseWithMessage(
        $data = [],
        $message = '',
        $code = Response::HTTP_SERVICE_UNAVAILABLE
    ): JsonResponse {
        return response()->json(['code' => $code, 'message' => $message, 'data' => $data], $code);

    }

    protected function errorResponse($message, $code, $data = [])
    {
        $this->errorResponseWithMessage($data, $message, $code);
    }
}
