<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class GuidesNotFoundException extends AbstractException
{
    const exceptionName = "guides not found";
    const exceptionCode = 500;
}
