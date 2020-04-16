<?php

namespace App\Cache;

trait DraftCacheTags
{
    protected static function getDraftTag(): string
    {
        return "DraftTag";
    }

    protected static function getDraftAgentTag(int $agentId): string
    {
        return self::getDraftTag() . "|$agentId";
    }
}
