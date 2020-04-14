<?php


namespace App\Exceptions;

use Benfin\Requests\Exceptions\AbstractException;

class StatisticsNotFoundException extends AbstractException
{
    const exceptionName = "statistics not found";
    const exceptionCode = 404;
}
