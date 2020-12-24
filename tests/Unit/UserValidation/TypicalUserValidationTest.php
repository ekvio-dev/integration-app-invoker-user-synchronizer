<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Unit\UserValidation;

use Ekvio\Integration\Invoker\UserSyncPipelineData;
use Ekvio\Integration\Invoker\UserValidation\TypicalUserValidator;
use PHPUnit\Framework\TestCase;

/**
 * Class TypicalUserValidationTest
 * @package Ekvio\Integration\Invoker\Tests\Unit\UserValidation
 */
class TypicalUserValidationTest extends TestCase
{
    private function user(): array
    {
        return [
            'login' => 'test',
            'first_name' => 'Дмитрий',
            'last_name' => 'Иванов',
            'phone' => '79275000000',
            'email' => null,
            'groups' => [
                'region' => 'region',
                'city' => 'city',
                'role' => 'role',
                'position' => 'position',
                'team' => 'team',
                'department' => 'department',
                'assignment' => 'assignment',
            ]
        ];
    }

    private function buildPipeline(array $data): UserSyncPipelineData
    {
        $pipeline = new UserSyncPipelineData();
        $pipeline->addSource('test', $data);

        return $pipeline;
    }

    public function testSuccessUserValidation()
    {
        $validator = new TypicalUserValidator();

        $result = $validator->validate($this->buildPipeline([$this->user()]));
        $this->assertCount(1, $result->data());
        $this->assertCount(0, $result->logs());
    }

    public function testFalseUserLoginValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['login'] = null;

        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }

    public function testFalseFirstNameValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['first_name'] = null;

        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());

        $user['first_name'] = 'Ivanov';
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }

    public function testFalseLastNameValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['last_name'] = null;

        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());

        $user['last_name'] = 'Ivan';
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }

    public function testFalsePhoneValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['phone'] = null;

        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());

        $user['phone'] = 'n79275200000';
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
        $user['phone'] = '123456789';
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());;

        $user['phone'] = '12345678910111213';
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }

    public function testFalseEmailValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();

        $user['email'] = 'not-valid-email';
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }

    public function testSuccessEmailValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();

        $user['email'] = 'test@test.ru';
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(1, $result->data());
        $this->assertCount(0, $result->logs());
    }

    public function testFalseUserGroupsValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();

        unset($user['groups']['region']);
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }

    public function testZeroNameGroupValidation()
    {
        $users = [[
            'login' => 'test',
            'first_name' => 'Дмитрий',
            'last_name' => 'Иванов',
            'phone' => '79275000000',
            'email' => null,
            'groups' => [
                'region' => '0',
                'city' => '0',
                'role' => '0',
                'position' => '0',
                'team' => '0',
                'department' => '0',
                'assignment' => '0',
            ]
        ]];
        $validator = new TypicalUserValidator();
        $result = $validator->validate($this->buildPipeline($users));
        $this->assertCount(1, $result->data());
        $this->assertCount(0, $result->logs());
    }

    public function testUserValidationModifications()
    {
        $validator = new TypicalUserValidator([
            'loginValidator' => function() {
                return true;
            },
            'firstNameValidator' => function() {
                return true;
            },
            'lastNameValidator' => function() {
                return true;
            },
            'phoneValidator' => function() {
                return true;
            },
            'emailValidator' => function() {
                return true;
            },
            'groupsValidator' => function() {
                return true;
            },
        ]);

        $user =[
            'login' => null,
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'email' => null,
            'groups' => [
                'region' => null,
                'city' => null,
                'role' => null,
                'position' => null,
                'team' => null,
                'department' => null,
                'assignment' => null,
            ]
        ];

        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(1, $result->data());
        $this->assertCount(0, $result->logs());
    }

    public function testChiefEmailValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['chief_email'] = 'not_valid_email';

        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }
}