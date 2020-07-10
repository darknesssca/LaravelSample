<?php


namespace App\Exceptions\Qiwi;


use Benfin\Requests\Exceptions\AbstractException;

class ResolutionException extends AbstractException
{
    // Данный эксепшен требуется для специализированного проброса данных и прерывания работы механизма.
    // Использовать его где-либо крайне не рекомендуется, поскольку это может привести к нарушению работы логики приложения.
    const exceptionName = "qiwi resolution error";
    const exceptionCode = 404;
}
