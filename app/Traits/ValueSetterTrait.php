<?php


namespace App\Traits;


trait ValueSetterTrait
{
    public function setValuesByArray(&$target, $dependencies, $source)
    {
        foreach ($dependencies as $targetName => $sourceName) {
            if (isset($source[$sourceName]) && $source[$sourceName]) {
                if (gettype($source[$sourceName]) == 'array') {
                    continue;
                }
                $target[$targetName] = $source[$sourceName];
            }
        }
    }
}