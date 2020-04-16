<?php


namespace App\Contracts\Repositories\Services;


interface DraftServiceContract
{
    public function getDraftByAgentId();
    public function getById($id);
    public function create($attributes):int;
    public function update($draftId, $attributes):void;
    public function delete($draftId):void;
    public function getByFilter(array $attributes);
}
