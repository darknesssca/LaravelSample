<?php

namespace App\Utils;

use App\Traits\CacheStore;
use App\Contracts\Utils\DeferredResultContract;

class DeferredResult implements DeferredResultContract
{
    use CacheStore;

    protected $cacheStorePrefix = '';

    const ERROR = 'error';
    const WAITING = 'process';
    const DONE = 'done';

    public function get(string $id): ?array
    {
        $result = $this->look($id);

        if ($result['status'] != self::WAITING) {
            $this->flush($id);
        }

        return $result;
    }

    public function break(string $id, string $status = null): array
    {
        $this->flush($id);

        return [
            'status' => $status ?? self::DONE,
        ];
    }

    public function done(string $id, $data = null, int $lifetime = 300): void
    {
        $data = [
            'result' => $data,
            'status' => self::DONE,
            'id' => $id,
        ];

        $this->put($id, $data, $lifetime);
    }

    public function error(string $id, $data = null, int $lifetime = 300): void
    {
        $data = [
            'result' => $data,
            'status' => self::ERROR,
            'id' => $id,
        ];

        $this->put($id, $data, $lifetime);
    }

    public function process(string $id, $data = null, int $lifetime = 300): void
    {
        $data = [
            'result' => $data,
            'status' => self::WAITING,
            'id' => $id,
        ];

        $this->put($id, $data, $lifetime);
    }

    public function getErrorStatus(): string
    {
        return static::ERROR;
    }

    public function getWaitingStatus(): string
    {
        return static::WAITING;
    }

    public function getDoneStatus(): string
    {
        return static::DONE;
    }

    public function getInitialResponse(string $id, string $status = null): array
    {
        return [
            'result' => null,
            'status' => $status ?? self::WAITING,
            'id' => $id,
        ];
    }
}
