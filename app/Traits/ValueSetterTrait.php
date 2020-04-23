<?php


namespace App\Traits;


trait ValueSetterTrait
{
    public function setValuesByArray(&$target, $dependencies, $source)
    {
        foreach ($dependencies as $targetName => $sourceName) {
            if (
                isset($source[$sourceName]) && $source[$sourceName] ||
                isset($source[$sourceName]) && gettype($source[$sourceName]) == 'boolean'
            ) {
                if (gettype($source[$sourceName]) == 'array') {
                    continue;
                }
                $target[$targetName] = $source[$sourceName];
            }
        }
    }

    public function setValuesByArrayWithEmptyString(&$target, $dependencies, $source)
    {
        foreach ($dependencies as $targetName => $sourceName) {
            if (isset($source[$sourceName])) {
                if (gettype($source[$sourceName]) == 'array') {
                    continue;
                }
                if ($source[$sourceName] || gettype($source[$sourceName]) === 'boolean') {
                    $target[$targetName] = $source[$sourceName];
                } else {
                    $target[$targetName] = null;
                }
            }
        }
    }
}
