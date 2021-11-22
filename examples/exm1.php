<?php

declare(strict_types=1);

use Ekvio\Integration\Contracts\Extractor;
use Ekvio\Integration\Contracts\Profiler;
use Ekvio\Integration\Invoker\UserCollector\ExtractorPriorityCollector;
use Ekvio\Integration\Invoker\UserFactory\TypicalUserFactory;
use Ekvio\Integration\Invoker\UserSynchronizer;
use Ekvio\Integration\Invoker\UserValidation\TypicalUserValidator;
use Ekvio\Integration\Sdk\V2\EqueoClient;
use Ekvio\Integration\Sdk\V2\Integration\IntegrationResult;
use Ekvio\Integration\Sdk\V2\User\UserApi;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

require_once __DIR__ . '/../vendor/autoload.php';

class Extractor1 implements Extractor
{
    private $name;
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function extract(array $options = []): array
    {
        return [
            [
                'USR_LOGIN' => 'test1',
                'USR_FIRST_NAME' => 'Иван',
                'USR_LAST_NAME' => 'Иывнов',
                'USR_MOBILE' => '89275000001',
                'USR_EMAIL' => 'test1@test.dev',
                'MANAGER_EMAIL' => 'manager@test.dev',
                'USR_UDF_USER_FIRED' => '0',
                'REGION_NAME' => 'region',
                'CITY_NAME' => 'city',
                'ROLE' => 'role',
                'POSITION_NAME' => 'position',
                'TEAM_NAME' => 'team',
                'DEPARTAMENT_NAME' => 'department',
                'ASSIGNMENT_NAME' => 'assignment',
            ],
            [
                'USR_LOGIN' => 'test2',
                'USR_FIRST_NAME' => 'Петр',
                'USR_LAST_NAME' => 'Петров',
                'USR_MOBILE' => '89275000002',
                'USR_EMAIL' => 'test2@test.dev',
                'MANAGER_EMAIL' => 'manager@test.dev',
                'USR_UDF_USER_FIRED' => '0',
                'REGION_NAME' => 'region',
                'CITY_NAME' => 'city',
                'ROLE' => 'role',
                'POSITION_NAME' => 'position',
                'TEAM_NAME' => 'team',
                'DEPARTAMENT_NAME' => 'department',
                'ASSIGNMENT_NAME' => 'assignment',
            ],
        ];
    }

    public function name(): string
    {
        return $this->name;
    }
}

class Extractor2 implements Extractor
{
    private $name;
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function extract(array $options = []): array
    {
        return [
            [],
            ['login' => 'afro'],
            [
                'USR_LOGIN' => 'test1',
                'USR_FIRST_NAME' => 'Иван',
                'USR_LAST_NAME' => 'Иывнов',
                'USR_MOBILE' => '89275000001',
                'USR_EMAIL' => 'test1@test.dev',
                'MANAGER_EMAIL' => 'manager@test.dev',
                'USR_UDF_USER_FIRED' => '0',
                'REGION_NAME' => 'region',
                'CITY_NAME' => 'city',
                'ROLE' => 'role',
                'POSITION_NAME' => 'position',
                'TEAM_NAME' => 'team',
                'DEPARTAMENT_NAME' => 'department',
                'ASSIGNMENT_NAME' => 'assignment',
            ],
            [
                'USR_LOGIN' => 'test3',
                'USR_FIRST_NAME' => 'Сидр',
                'USR_LAST_NAME' => 'Сидоров',
                'USR_MOBILE' => '89275000003',
                'USR_EMAIL' => 'test3@test.dev',
                'MANAGER_EMAIL' => 'manager@test.dev',
                'USR_UDF_USER_FIRED' => '0',
                'REGION_NAME' => 'region',
                'CITY_NAME' => 'city',
                'ROLE' => 'role',
                'POSITION_NAME' => 'position',
                'TEAM_NAME' => 'team',
                'DEPARTAMENT_NAME' => 'department',
                'ASSIGNMENT_NAME' => 'assignment',
            ],
        ];
    }

    public function name(): string
    {
        return $this->name;
    }
}

class DumpProfiler implements Profiler
{
    public function profile(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}

class EmailBuilder
{
    public function __invoke(string $source, string $index, array $user)
    {
        return 'aaa@dev.ru';
    }
}

class HttpDummyResult implements IntegrationResult
{
    public function get(string $url): string
    {
        return '{"data": [{"index":0,"login":"testru","status":"error","errors":[{"code":1007,"field":"groups","message":"Group path invalid format. Group index: root_1"}]}]}';
    }
}

$emailBuilder = new EmailBuilder();

$mock = new MockHandler([
    new Response(200, [], '{"data": {"integration": 1}}'),
    new Response(200, [], '{"data": {"status": "completed", "file": "link-me"}}')
]);

$httpClient = new Client([
    'handler' => HandlerStack::create($mock),
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ],
    'http_errors' => false,
    'debug' => (bool) getenv('HTTP_CLIENT_DEBUG'),
    'verify' => false
]);

$prior = 'hr.csv';

(new UserSynchronizer(
    new ExtractorPriorityCollector($prior, [
        'hr2.csv' => new Extractor2('some name'),
        $prior => new Extractor1($prior)
    ]),
    new TypicalUserFactory(),
    new TypicalUserValidator(),
    new UserApi(new EqueoClient($httpClient, new HttpDummyResult(), 'http://nginx', '111222')),
    new DumpProfiler()
))();
