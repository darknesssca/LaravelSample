<?php


namespace App\Traits;

trait TransformBoolean
{
    protected function transformAnyToBoolean($boolean)
    {
        return (bool)$boolean;
    }

    protected function transformBooleanToInteger($boolean)
    {
        return (int)$boolean;
    }

    protected function transformBooleanToChar($boolean)
    {
        return $boolean ? 'Y' : 'N';
    }
}
