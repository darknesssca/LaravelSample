<?php


namespace App\Contracts\Company\Vsk;


interface VskErrorHandlerServiceContract
{
    public function checkError(string $token, array $parsed_response);

    public function writeErrorsToToken($token = null, $errors = null);
}
