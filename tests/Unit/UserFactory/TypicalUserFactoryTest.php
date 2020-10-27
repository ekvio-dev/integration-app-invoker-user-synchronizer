<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Unit\UserFactory;

use Ekvio\Integration\Invoker\UserFactory\TypicalUserFactory;
use Ekvio\Integration\Invoker\UserSyncData;
use Ekvio\Integration\Invoker\UserSyncPipelineData;
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

    private function buildPipeline(array $data): UserSyncPipelineData
    {
        $pipeline = new UserSyncPipelineData();
        $pipeline->addSource('test', $data);

        return $pipeline;
    }

    public function testBuildUserFromEmptyRaw()
    {
        $factory = (new TypicalUserFactory())->build($this->buildPipeline([]));
        $this->assertEquals([], $factory->data());
    }

    public function testBuildUserFromOneUser()
    {
        $factory = (new TypicalUserFactory())->build($this->buildPipeline([['login' => 'test', 'first_name' => 'Ivan']]));
        $this->assertCount(1, $factory->data());
    }

    public function testBuildUserFromDefaultMap()
    {
        $factory = (new TypicalUserFactory())->build($this->buildPipeline([['login' => '']]));
        /** @var UserSyncData $user */
        $user = $factory->data()[0];

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
        ], $user->data());
    }

    public function testBuildUserWithCustomDefaultGroups()
    {
        $factory = (new TypicalUserFactory(['groupDefaults' => [
            'groups.region' => 'custom region',
            'groups.city' => 'custom city',
            'groups.role' => 'custom role',
            'groups.position' => 'custom position',
            'groups.team' => 'custom team',
            'groups.department' => 'custom department',
            'groups.assignment' => 'custom assignment',
        ]]))->build($this->buildPipeline([['login' => '']]));

        /** @var UserSyncData $user */
        $user = $factory->data()[0];

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
        ], $user->data());
    }

    public function testBuildUserFromMap()
    {
        $factory = (new TypicalUserFactory())->build($this->buildPipeline([$this->user()]));
        /** @var UserSyncData $user */
        $user = $factory->data()[0];

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
        ], $user->data());
    }

    public function testBuildUserWithCustomActiveStatus()
    {
        $fake = $this->user();
        $fake['USR_UDF_USER_FIRED'] = 'actual';

        $factory = (new TypicalUserFactory(['activeStatus' => 'actual']))
            ->build($this->buildPipeline([$fake]));
        /** @var UserSyncData $user */
        $user = $factory->data()[0];

        $this->assertEquals('active', $user->data()['status']);
    }

    public function testBuildUserWithForms()
    {
        $fake = $this->user();
        $fake['OTCH'] = 'Peter';
        $fake['BIRTH'] = '20.01.16';
        $fake['FLAG1'] = 'test';
        $fake['FLAG2'] = null;

        $factory = (new TypicalUserFactory(['forms' => [
            "1" => "OTCH",
            "2" => "BIRTH",
            4 => "FLAG1",
            "5" => 'FLAG2',
            "100" => "UNKNOWN_FORM"
        ]]))->build($this->buildPipeline([$fake]));
        /** @var UserSyncData $user */
        $user = $factory->data()[0];

        $this->assertEquals([
            "1" => "Peter",
            "2" => "20.01.16",
            "4" => "test",
            "5" => null,
            "100" => null
        ], $user->data()['forms']);
    }

    public function testBuildUserWithCallableModifications()
    {
        $factory = (new TypicalUserFactory([
            'loginBuilder' => static function() {
                return 'my.login';
            },
            'firstNameBuilder' => static function() {
                return 'my.first_name';
            },
            'lastNameBuilder' => static function() {
                return 'my.last_name';
            },
            'emailBuilder' => static function() {
                return null;
            },
            'phoneBuilder' => static function() {
                return null;
            },
            'verifiedEmailBuilder' => static function() {
                return true;
            },
            'verifiedPhoneBuilder' => static function() {
                return true;
            },
            'chiefEmailBuilder' => static function() {
                return null;
            },
            'statusBuilder' => static function() {
                return 'blocked';
            },
            'groupRegionBuilder' => static function() {
                return 'my.region';
            },
            'groupCityBuilder' => static function() {
                return 'my.city';
            },
            'groupRoleBuilder' => static function() {
                return 'my.role';
            },
            'groupPositionBuilder' => static function() {
                return 'my.position';
            },
            'groupTeamBuilder' => static function() {
                return 'my.team';
            },
            'groupDepartmentBuilder' => static function() {
                return 'my.department';
            },
            'groupAssignmentBuilder' => static function() {
                return 'my.assignment';
            },
        ]))->build($this->buildPipeline([$this->user()]));
        /** @var UserSyncData $user */
        $user = $factory->data()[0];

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
        ], $user->data());
    }

    public function testBuildUserWithBeforeModification()
    {
        $factory = (new TypicalUserFactory([
            'beforeBuild' => static function(): array {
                return [];
            }
        ]))->build($this->buildPipeline([$this->user()]));
        $users = $factory->data();

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

        $factory = (new TypicalUserFactory())
            ->build($this->buildPipeline($users));
        /** @var UserSyncData $user */
        $user = $factory->data()[0];

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
        ], $user->data());
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

        $factory = (new TypicalUserFactory(['useGroupDefaults' => false]))
            ->build($this->buildPipeline($users));
        /** @var UserSyncData $user */
        $user = $factory->data()[0];

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
        ], $user->data());
    }
}