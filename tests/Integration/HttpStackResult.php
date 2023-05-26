<?php

declare(strict_types=1);


namespace Ekvio\Integration\Invoker\Tests\Integration;


use Ekvio\Integration\Sdk\Common\Integration\IntegrationResult;

class HttpStackResult implements IntegrationResult
{
    private $stack;
    public function __construct(array $stackResult)
    {
        $this->stack = $stackResult;
    }
    public function get(string $url): string
    {
        return array_shift($this->stack);
    }
}