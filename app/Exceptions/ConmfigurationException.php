<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class ConmfigurationException extends AbstractException
{
    const exceptionName = "configuration error";
    const exceptionCode = 500;
}
