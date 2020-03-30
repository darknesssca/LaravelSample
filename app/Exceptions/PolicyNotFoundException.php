<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class PolicyNotFoundException extends AbstractException
{
    const exceptionName = "policy not found";
    const exceptionCode = 404;
}
