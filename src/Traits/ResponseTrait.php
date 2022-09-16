<?php

namespace Litermi\Response\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Litermi\ErrorNotification\Services\GetInfoFromExceptionService;
use Litermi\Logs\Facades\LogConsoleFacade;
use Litermi\Logs\Services\GetTrackerService;
use Litermi\Logs\Services\SendLogUserRequestResponseService;
use Litermi\Response\Services\GetResponseClientExceptionService;

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

        if ($code < Response::HTTP_CONTINUE || $code > Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED) {
            $code = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        $infoException = GetInfoFromExceptionService::execute($exception);
        SendLogUserRequestResponseService::execute($infoException);
        LogConsoleFacade::full()->tracker()->log('catch-error: ' . $exception->getMessage(), $infoException);


        [$code, $message, $infoException] = GetResponseClientExceptionService::execute(
            $code,
            $message,
            $infoException,
            $exception
        );
        $error = $this->reformatError($message);
        if (env('APP_DEBUG') === true) {
            return $this->errorResponseWithMessage($infoException, $message, $code, $error);
        }

        return $this->errorResponseWithMessage([], $message, $code, $error);

    }

    public function errorResponseWithMessage(
        $data = [],
        $message = '',
        $code = Response::HTTP_SERVICE_UNAVAILABLE,
        $error = []
    ): JsonResponse {
        return response()->json(
            ['error' => $error, 'code' => $code, 'message' => $message, 'data' => $data],
            $code
        );
    }

    /**
     * @param $message
     * @param $code
     * @param $data
     * @return mixed
     */
    public function errorResponse($message, $code, $data = []) {
        if ($code < Response::HTTP_CONTINUE || $code > Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED) {
            $code = Response::HTTP_SERVICE_UNAVAILABLE;
        }
        $error = $this->reformatError($message);
        return response()->json(['error' => $error, 'code' => $code, 'message'=> $message, 'data' => $data], $code);
    }

    /**
     * @param $message
     * @return array|\string[][]
     */
    public function reformatError($message): array
    {
        $error = [];
        if (is_string($message)) {
            $error = ['message' => [$message]];
        }
        if(is_array($message)){
            $error = $message;
        }
        return $error;
    }
}
