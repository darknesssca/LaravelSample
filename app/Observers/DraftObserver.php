<?php


namespace App\Observers;

use App\Models\Draft;
use Benfin\Api\GlobalStorage;

trait DraftObserver
{
    protected static function boot()
    {
        parent::boot();

        Draft::updated(function ($draft) {
            dd("updated -> $draft");
        });

        Draft::updating(function ($draft) {
            dd("updating -> $draft");
        });

        Draft::created(function ($draft) {
            dd("created -> $draft");
        });

        Draft::creating(function ($draft) {
            dd("creating -> $draft");
        });

    }

}
