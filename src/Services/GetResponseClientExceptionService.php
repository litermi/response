<?php

namespace Litermi\Response\Services;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

/**
 *
 */
class GetResponseClientExceptionService
{

    /**
     * @param $message
     * @param Exception $exception
     * @return array|null
     */
    public static function execute(
        $code,
        $message,
        $data,
        Exception $exception
    ): ?array {
        if ($exception instanceof ClientException || $exception instanceof RequestException) {
            return self::getData($exception);
        }
        return [$code, $message, $data];

    }

    /**
     * @param $exception
     * @return array
     */
    private static function getData($exception): ?array
    {
        try {
            $message        = __('error external service');
            $responseBody   = $exception->getResponse()
                ->getBody();
            $code           = $exception->getCode();
            $data[ 'code' ] = $code;
            $host           = $exception->getRequest()
                ->getUri()
                ->getHost();
            $error          = json_decode($responseBody->getContents());
            $error          = $error === null || is_bool($error) ? (object)[] : $error;
            if (($host !== null) && (is_string($host) === true)) {
                $error->host = $host;
            }
            $message                  = property_exists($error, 'message') ? $error->message : '';
            $data[ 'error_external' ] = $error;

            $data[ 'response_body' ] = $responseBody;
            if (config('app.debug') === false) {
                $data = [];
            }
            return [$code, $message, $data];
        } catch (Exception $exception) {
            $code                    = $exception->getCode();
            $data[ 'message' ]       = __('error external service');
            $data[ 'code' ]          = $code;
            $data[ 'error_explain' ] = $exception->getMessage();
            $data[ 'file' ]          = $exception->getFile();
            $data[ 'line' ]          = $exception->getLine();

            if (config('app.debug') === false) {
                $data = [];
            }

            return [$code, $message, $data];

        }

    }

}
