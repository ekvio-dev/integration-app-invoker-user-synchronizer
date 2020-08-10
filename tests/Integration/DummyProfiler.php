<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Integration;

use Ekvio\Integration\Contracts\Profiler;

/**
 * Class DummyProfiler
 * @package Ekvio\Integration\Invoker\Tests\Integration
 */
class DummyProfiler implements Profiler
{
    /**
     * @param string $message
     */
    public function profile(string $message): void
    {
    }
}