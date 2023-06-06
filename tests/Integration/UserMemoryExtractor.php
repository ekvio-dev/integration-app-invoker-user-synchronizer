<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Integration;

use Ekvio\Integration\Contracts\Extractor;

/**
 * Class UserMemoryExtractor
 * @package Ekvio\Integration\Invoker\Tests\Integration
 */
class UserMemoryExtractor implements Extractor
{

    private $users = [];

    public function __construct(array $users = [])
    {
        $this->users = $users;
    }
    public function extract(array $options = []): array
    {
        return count($this->users) > 0 ? $this->users :
            [
            [
                'USR_LOGIN' => 'test',
                'USR_FIRST_NAME' => 'Иван',
                'USR_LAST_NAME' => 'Иванов',
                'USR_MOBILE' => '89275000000',
                'USR_EMAIL' => 'test@test.dev',
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
                'USR_FIRST_NAME' => null,
                'USR_LAST_NAME' => 'ivanov',
                'USR_MOBILE' => '89275000000',
                'USR_EMAIL' => 'test@test.dev',
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
            ],
        ];
    }

    public function name(): string
    {
        return 'Memory extractor';
    }
}