<?php

namespace App\Contracts\Utils;

interface DeferredResultContract
{
    public function exist(string $id): bool;

    public function get(string $id): ?array;

    public function look(string $id): ?array;

    public function break(string $id, string $status = null): array;

    public function done(string $id, $data = null, int $lifetime = 300): void;

    public function error(string $id, $data = null, int $lifetime = 300): void;

    public function process(string $id, $data = null, int $lifetime = 300): void;

    public function flush(string $id): void;

    public function getId(...$args): string;

    public function getErrorStatus(): string;

    public function getWaitingStatus(): string;

    public function getDoneStatus(): string;

    public function getInitialResponse(string $id, string $status = null): array;
}
