<?php


namespace App\Exceptions\Qiwi;


use Benfin\Requests\Exceptions\AbstractException;

class BillingDeclinedException extends AbstractException
{
    // Данный эксепшен требуется для специализированного проброса данных и прерывания работы механизма.
    // Использовать его где-либо крайне не рекомендуется, поскольку это может привести к нарушению работы логики приложения.
    const exceptionName = "qiwi billing error";
    const exceptionCode = 404;
}
