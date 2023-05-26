<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Integration;

use Ekvio\Integration\Invoker\UserCollector\ExtractorPriorityCollector;
use Ekvio\Integration\Invoker\UserFactory\TypicalUserFactory;
use Ekvio\Integration\Invoker\UserSynchronizer;
use Ekvio\Integration\Invoker\UserValidation\TypicalUserValidator;
use Ekvio\Integration\Sdk\V3\EqueoClient;
use Ekvio\Integration\Sdk\V3\User\UserApi;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class UserSynchronizerTest
 * @package Ekvio\Integration\Invoker\Tests\Integration
 */
class UserSynchronizerTest extends TestCase
{
    public function testIntegrationUsageUserSynchronizer()
    {

        $responses = [
            new Response(200, [], '{"data":{"integration":100}}'),
            new Response(200, [], '{"data":{"status":"completed", "file": "http://test.dev/link/1"}}'),
            new Response(200, [], '{"data":{"integration":101}}'),
            new Response(200, [], '{"data":{"status":"completed", "file": "http://test.dev/link/2"}}'),
        ];

        $container = [];
        $httpClient = $this->getMockClient($container, $responses);
        $equeoClient = new EqueoClient($httpClient, new HttpStackResult([
            '{"data": [{"index": 1,"login":"test3","status":"created","data":{"password":"123","status":"blocked"}}]}',
            '{"data": [{"index": 0,"login":"test1","status":"created","data":{"password":"321","status":"active"}}]}'
        ]), 'http://test.dev', '123');

        $collector = new ExtractorPriorityCollector('hr', [
            'hr' => new UserMemoryExtractor,
        ]);

        $synchronizer = new UserSynchronizer(
            $collector,
            new TypicalUserFactory(),
            new TypicalUserValidator(),
            new UserApi($equeoClient),
            new DummyProfiler()
        );

        $result = $synchronizer();
        $this->assertCount(2, $result->data());
    }

    private function getMockClient(array &$container, array $responses = []): Client
    {
        if(!$responses) {
            $responses = [new Response(200, ['X-Foo' => 'Bar'], '{"data": []}')];
        }

        $mock = new MockHandler($responses);
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        return new Client(['handler' => $handlerStack]);
    }
}