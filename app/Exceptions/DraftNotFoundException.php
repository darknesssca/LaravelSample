<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class DraftNotFoundException extends AbstractException
{
    const exceptionName = "draft not found";
    const exceptionCode = 404;
}
