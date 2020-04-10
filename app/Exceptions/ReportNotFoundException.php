<?php


namespace App\Exceptions;


use Benfin\Requests\Exceptions\AbstractException;

class ReportNotFoundException extends AbstractException
{
    const exceptionName = "Отчеты по выплатам не найдены";
    const exceptionCode = 404;
}
