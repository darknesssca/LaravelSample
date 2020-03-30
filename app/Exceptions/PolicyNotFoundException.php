<?php


namespace App\Exceptions;


class PolicyNotFoundException extends AbstractException
{
    const exceptionName = "policy not found";
    const exceptionCode = 404;
}
