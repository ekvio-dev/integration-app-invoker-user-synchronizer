<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Integration;

use Ekvio\Integration\Sdk\V2\User\UserSync;

/**
 * Class UserApiDummy
 * @package Ekvio\Integration\Invoker\Tests\Integration
 */
class UserApiDummy implements UserSync
{
    public function sync(array $users): array
    {
        return [
            [
                "index" => 2,
                "login" => 'test3',
                "status" => "error",
                "errors" => [
                    "code" => 1007,
                    "field" => "email",
                    "message" => "Email not unique"
                ]
            ],
            [
                "index" => 1,
                "login" => "test",
                "status" => "created",
                "data" => [
                    "password" => "2buHZCBk",
                    "status" => "active"
                ]
            ],
            [
                "index" => null,
                "login" => "Test01",
                "status" => "updated",
                "data" => [
                    "status" => "blocked"
                ]
            ]
        ];
    }
}