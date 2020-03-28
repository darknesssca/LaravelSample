<?php


namespace App\Exceptions;


class ValidationException extends AbstractException
{
    const exceptionType = "validation error";
}
