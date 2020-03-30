<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class CompanyException extends AbstractException
{
    const exceptionName = "company error";
    const exceptionCode = 404;
}
