<?php
declare(strict_types=1);

use Ekvio\Integration\Contracts\Extractor;
use Ekvio\Integration\Contracts\Profiler;
use Ekvio\Integration\Invoker\UserCollector\ExtractorPriorityCollector;
use Ekvio\Integration\Invoker\UserFactory\TypicalUserFactory;
use Ekvio\Integration\Invoker\UserSynchronizer;
use Ekvio\Integration\Invoker\UserValidation\TypicalUserValidator;
use Ekvio\Integration\Sdk\V2\EqueoClient;
use Ekvio\Integration\Sdk\V2\Integration\HttpIntegrationResult;
use Ekvio\Integration\Sdk\V2\User\User;
use GuzzleHttp\Client;

require_once __DIR__ . '/../vendor/autoload.php';

class Extractor1 implements Extractor
{
    public const NAME = 'extractor 1';
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
        return self::NAME;
    }
}

class Extractor2 implements Extractor
{
    public const NAME = 'extractor 2';
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
        return self::NAME;
    }
}

class DumpProfiler implements Profiler
{
    public function profile(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}

$httpClient = new Client([
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ],
    'http_errors' => false,
    'debug' => (bool) getenv('HTTP_CLIENT_DEBUG'),
    'verify' => false
]);

(new UserSynchronizer(
    new ExtractorPriorityCollector(Extractor2::NAME, [new Extractor2(), new Extractor1()]),
    new TypicalUserFactory(),
    new TypicalUserValidator(),
    new User(new EqueoClient($httpClient, new HttpIntegrationResult(), 'http://nginx', '111222')),
    new DumpProfiler()
))();