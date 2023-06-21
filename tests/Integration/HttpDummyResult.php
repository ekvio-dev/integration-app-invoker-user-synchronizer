<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Integration;

use Ekvio\Integration\Sdk\Common\Integration\IntegrationResult;

class HttpDummyResult implements IntegrationResult
{
    private $response;
    public function __construct(string $response = '{"data":[]}')
    {
        $this->response = $response;
    }

    public function get(string $url): string
    {
        return $this->response;
    }
}