<?php

namespace App\Exceptions;

use Exception;

class ApiRequestException extends Exception
{
    public $responseBody;

    public function __construct($responseBody = null)
    {
        $this->responseBody = $responseBody;
    }

    public function render($request)
    {
        return response()->json( $this->responseBody, 400);
    }
}