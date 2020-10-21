<?php


namespace App\Contracts\Company\Vsk;


interface VskCallbackServiceContract
{
    public function runNextStep(array $callback_response);
}
