<?php

namespace App\Traits;

use Carbon\Carbon;
use ReflectionClass;

trait DispatchesJob
{
    /**
     * @param $job
     * @param array $payload
     * @param Carbon|null $when
     * @return mixed
     * @throws \ReflectionException
     */
    public function dispatchJob($job, $payload = [], Carbon $when = null)
    {
        $payload = is_array($payload) ? $payload : [$payload];
        $class = new ReflectionClass($job);
        $instance = $class->newInstanceArgs($payload);
        return $when ? dispatch($instance)->delay($when) : dispatch_now($instance);

    }
}
