<?php


namespace App\Exceptions;


class ApiRequestsException extends AbstractException
{
    const exceptionName = "api request error";
    const exceptionCode = 500;
}
