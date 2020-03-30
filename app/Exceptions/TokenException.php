<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class TokenException extends AbstractException
{
    const exceptionName = "token error";
    const exceptionCode = 500;
}
