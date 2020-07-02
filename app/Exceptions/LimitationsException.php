<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class LimitationsException extends AbstractException
{
    const exceptionName = "limitations error";
    const exceptionCode = 400;
}
