<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class AutocodException extends AbstractException
{
    const exceptionName = "autocod error";
    const exceptionCode = 400;
}
