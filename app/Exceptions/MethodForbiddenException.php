<?php


namespace App\Exceptions;


class MethodForbiddenException extends AbstractException
{
    const exceptionName = "forbidden method";
    const exceptionCode = 403;
}
