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

    public function testOnlyBlockedUsers()
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects($this->once())
            ->method('sync')
            ->with(array (
                0 =>
                    array (
                        'login' => 'test3',
                        'first_name' => 'Сидоров',
                        'last_name' => 'Антон',
                        'chief_email' => 'manager@test.dev',
                        'status' => 'blocked',
                        'email' => 'test@test.dev',
                        'phone' => '79275000000',
                        'groups' =>
                            array (
                                'region' =>
                                    array (
                                        'path' => 'region',
                                    ),
                                'role' =>
                                    array (
                                        'path' => 'role',
                                    ),
                                'position' =>
                                    array (
                                        'path' => 'position',
                                    ),
                                'team' =>
                                    array (
                                        'path' => 'team',
                                    ),
                                'department' =>
                                    array (
                                        'path' => 'department',
                                    ),
                                'assignment' =>
                                    array (
                                        'path' => 'assignment',
                                    ),
                            ),
                    ),
            ),['partial_sync' => true]);

        $collector = new ExtractorPriorityCollector('hr', [
            'hr' => new UserMemoryExtractor(
                [
                    [
                        'USR_LOGIN' => 'test3',
                        'USR_FIRST_NAME' => 'Сидоров',
                        'USR_LAST_NAME' => 'Антон',
                        'USR_MOBILE' => '89275000000',
                        'USR_EMAIL' => 'test@test.dev',
                        'MANAGER_EMAIL' => 'manager@test.dev',
                        'USR_UDF_USER_FIRED' => '1',
                        'REGION_NAME' => 'region',
                        'CITY_NAME' => 'city',
                        'ROLE' => 'role',
                        'POSITION_NAME' => 'position',
                        'TEAM_NAME' => 'team',
                        'DEPARTAMENT_NAME' => 'department',
                        'ASSIGNMENT_NAME' => 'assignment',
                    ]
                ]
            ),
        ]);

        (new UserSynchronizer(
            $collector,
            new TypicalUserFactory(),
            new TypicalUserValidator(),
            $userApi,
            new DummyProfiler()
        ))(['partial_sync' => false]);
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