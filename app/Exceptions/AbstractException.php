<?php


namespace App\Exceptions;


class AbstractException extends \Exception
{
    const exceptionName = "internal error";
    const exceptionCode = 500;

    protected $data = null;
    protected $code = 0;

    public function __construct($data = null, int $code = 0, \Throwable $previous = null)
    {
        $this->data = $data;
        $this->code = $code ?? static::exceptionCode;

        parent::__construct(
            static::exceptionName,
            $code,
            $previous
        );
    }

    public function getMessageData()
    {
        return $this->data;
    }

    public function getHttpCode()
    {
        return $this->code;
    }

    public function getExceptionName()
    {
        return static::exceptionName;
    }
}
