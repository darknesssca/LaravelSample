<?php


namespace App\Exceptions;


class MethodNotFoundException extends AbstractException
{
    const exceptionName = "method not found";
    const exceptionCode = 404;
}
