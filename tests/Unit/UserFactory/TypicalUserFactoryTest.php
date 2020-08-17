<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Unit\UserFactory;

use Ekvio\Integration\Invoker\UserFactory\TypicalUserFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class TypicalUserFactoryTest
 * @package Ekvio\Integration\Invoker\Tests\Unit\UserFactory
 */
class TypicalUserFactoryTest extends TestCase
{
    private function user(): array
    {
        return [
            'USR_LOGIN' => 'test',
            'USR_FIRST_NAME' => 'ivan',
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
        ];
    }

    public function testBuildUserFromEmptyRaw()
    {
        $factory = new TypicalUserFactory();
        $this->assertEquals([], $factory->build([]));
    }

    public function testBuildUserFromOneUser()
    {
        $factory = new TypicalUserFactory();
        $this->assertEquals([], $factory->build(['login' => 'test', 'first_name' => 'Ivan']));
    }

    public function testBuildUserFromDefaultMap()
    {
        $factory = new TypicalUserFactory();
        $users = $factory->build([['login' => '']]);
        $this->assertEquals([
            'login' => null,
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone' => null,
            'verified_email' => false,
            'verified_phone' => false,
            'chief_email' => null,
            'status' => 'blocked',
            'groups' => [
                'region' => 'Demo region',
                'city' => 'Demo city',
                'role' => 'Demo role',
                'position' => 'Demo position',
                'team' => 'Demo team',
                'department' => 'Demo department',
                'assignment' => 'Demo assignment',
            ]
        ], $users[0]);
    }

    public function testBuildUserWithCustomDefaultGroups()
    {
        $factory = new TypicalUserFactory(['groupDefaults' => [
            'groups.region' => 'custom region',
            'groups.city' => 'custom city',
            'groups.role' => 'custom role',
            'groups.position' => 'custom position',
            'groups.team' => 'custom team',
            'groups.department' => 'custom department',
            'groups.assignment' => 'custom assignment',
        ]]);
        $users = $factory->build([['login' => '']]);
        $this->assertEquals([
            'login' => null,
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone' => null,
            'verified_email' => false,
            'verified_phone' => false,
            'chief_email' => null,
            'status' => 'blocked',
            'groups' => [
                'region' => 'custom region',
                'city' => 'custom city',
                'role' => 'custom role',
                'position' => 'custom position',
                'team' => 'custom team',
                'department' => 'custom department',
                'assignment' => 'custom assignment',
            ]
        ], $users[0]);
    }

    public function testBuildUserFromMap()
    {
        $factory = new TypicalUserFactory();
        $users = $factory->build([$this->user()]);

        $this->assertEquals([
            'login' => 'test',
            'first_name' => 'ivan',
            'last_name' => 'ivanov',
            'phone' => '79275000000',
            'email' => 'test@test.dev',
            'verified_email' => true,
            'verified_phone' => true,
            'chief_email' => 'manager@test.dev',
            'status' => 'active',
            'groups' => [
                'region' => 'region',
                'city' => 'city',
                'role' => 'role',
                'position' => 'position',
                'team' => 'team',
                'department' => 'department',
                'assignment' => 'assignment',
            ]
        ], $users[0]);
    }

    public function testBuildUserWithCustomActiveStatus()
    {
        $factory = new TypicalUserFactory(['activeStatus' => 'actual']);
        $fake = $this->user();
        $fake['USR_UDF_USER_FIRED'] = 'actual';

        $users = $factory->build([$fake]);
        $this->assertEquals('active', $users[0]['status']);
    }

    public function testBuildUserWithForms()
    {
        $fake = $this->user();
        $fake['OTCH'] = 'Peter';
        $fake['BIRTH'] = '20.01.16';
        $fake['FLAG1'] = 'test';
        $fake['FLAG2'] = null;

        $factory = new TypicalUserFactory(['forms' => [
            "1" => "OTCH",
            "2" => "BIRTH",
            4 => "FLAG1",
            "5" => 'FLAG2',
            "100" => "UNKNOWN_FORM"
        ]]);

        $users = $factory->build([$fake]);
        $this->assertEquals([
            "1" => "Peter",
            "2" => "20.01.16",
            "4" => "test",
            "5" => null,
            "100" => null
        ], $users[0]['forms']);
    }

    public function testBuildUserWithCallableModifications()
    {
        $factory = new TypicalUserFactory([
            'loginBuilder' => static function(int $index, array $user) {
                return 'my.login';
            },
            'firstNameBuilder' => static function(int $index, array $user) {
                return 'my.first_name';
            },
            'lastNameBuilder' => static function(int $index, array $user) {
                return 'my.last_name';
            },
            'emailBuilder' => static function(int $index, array $user) {
                return null;
            },
            'phoneBuilder' => static function(int $index, array $user) {
                return null;
            },
            'verifiedEmailBuilder' => static function(int $index, array $user) {
                return true;
            },
            'verifiedPhoneBuilder' => static function(int $index, array $user) {
                return true;
            },
            'chiefEmailBuilder' => static function(int $index, array $user) {
                return null;
            },
            'statusBuilder' => static function(int $index, array $user) {
                return 'blocked';
            },
            'groupRegionBuilder' => static function(int $index, array $user) {
                return 'my.region';
            },
            'groupCityBuilder' => static function(int $index, array $user) {
                return 'my.city';
            },
            'groupRoleBuilder' => static function(int $index, array $user) {
                return 'my.role';
            },
            'groupPositionBuilder' => static function(int $index, array $user) {
                return 'my.position';
            },
            'groupTeamBuilder' => static function(int $index, array $user) {
                return 'my.team';
            },
            'groupDepartmentBuilder' => static function(int $index, array $user) {
                return 'my.department';
            },
            'groupAssignmentBuilder' => static function(int $index, array $user) {
                return 'my.assignment';
            },
        ]);

        $users = $factory->build([$this->user()]);
        $this->assertEquals([
            'login' => 'my.login',
            'first_name' => 'my.first_name',
            'last_name' => 'my.last_name',
            'phone' => null,
            'email' => null,
            'verified_email' => true,
            'verified_phone' => true,
            'chief_email' => null,
            'status' => 'blocked',
            'groups' => [
                'region' => 'my.region',
                'city' => 'my.city',
                'role' => 'my.role',
                'position' => 'my.position',
                'team' => 'my.team',
                'department' => 'my.department',
                'assignment' => 'my.assignment',
            ]
        ], $users[0]);
    }

    public function testBuildUserWithBeforeModification()
    {
        $factory = new TypicalUserFactory([
            'beforeBuild' => static function(int $index, array $user): array {
                return [];
            }
        ]);

        $user = $this->user();
        $users = $factory->build([$user]);
        $this->assertEquals([], $users);
    }

    public function testBuildUserWithDefaultGroups()
    {
        $users = [
            [
                'USR_LOGIN' => 'test',
                'USR_FIRST_NAME' => 'ivan',
                'USR_LAST_NAME' => 'ivanov',
                'USR_MOBILE' => '89275000000',
                'USR_EMAIL' => 'test@test.dev',
                'MANAGER_EMAIL' => 'manager@test.dev',
                'USR_UDF_USER_FIRED' => '0',
                'REGION_NAME' => '',
                'CITY_NAME' => null,
                'ROLE' => '',
                'POSITION_NAME' => null,
                'TEAM_NAME' => '',
                'DEPARTAMENT_NAME' => '',
                'ASSIGNMENT_NAME' => null,
            ]
        ];

        $factory = new TypicalUserFactory();
        $users = $factory->build($users);
        $this->assertEquals([
            'login' => 'test',
            'first_name' => 'ivan',
            'last_name' => 'ivanov',
            'phone' => '79275000000',
            'email' => 'test@test.dev',
            'verified_email' => true,
            'verified_phone' => true,
            'chief_email' => 'manager@test.dev',
            'status' => 'active',
            'groups' => [
                'region' => 'Demo region',
                'city' => 'Demo city',
                'role' => 'Demo role',
                'position' => 'Demo position',
                'team' => 'Demo team',
                'department' => 'Demo department',
                'assignment' => 'Demo assignment',
            ]
        ], $users[0]);
    }

    public function testBuildUserWithoutDefaultGroups()
    {
        $users = [
            [
                'USR_LOGIN' => 'test',
                'USR_FIRST_NAME' => 'ivan',
                'USR_LAST_NAME' => 'ivanov',
                'USR_MOBILE' => '89275000000',
                'USR_EMAIL' => 'test@test.dev',
                'MANAGER_EMAIL' => 'manager@test.dev',
                'USR_UDF_USER_FIRED' => '0',
                'REGION_NAME' => '',
                'CITY_NAME' => null,
                'ROLE' => '',
                'POSITION_NAME' => null,
                'TEAM_NAME' => '',
                'DEPARTAMENT_NAME' => '',
                'ASSIGNMENT_NAME' => null,
            ]
        ];

        $factory = new TypicalUserFactory(['useGroupDefaults' => false]);
        $users = $factory->build($users);
        $this->assertEquals([
            'login' => 'test',
            'first_name' => 'ivan',
            'last_name' => 'ivanov',
            'phone' => '79275000000',
            'email' => 'test@test.dev',
            'verified_email' => true,
            'verified_phone' => true,
            'chief_email' => 'manager@test.dev',
            'status' => 'active',
            'groups' => [
                'region' => null,
                'city' => null,
                'role' => null,
                'position' => null,
                'team' => null,
                'department' => null,
                'assignment' => null,
            ]
        ], $users[0]);
    }
}