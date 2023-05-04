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
                ['path' => 'region'],
                ['path' => 'city'],
                ['path' => 'role'],
                ['path' => 'position'],
                ['path' => 'team'],
                ['path' => 'department'],
                ['path' => 'assignment'],
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

    public function testPhoneValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        unset($user['phone']);

        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(1, $result->data());
        $this->assertCount(0, $result->logs());

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

    public function testEmailValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();

        unset($user['email']);
        $result = $validator->validate($this->buildPipeline([$user]));
        $this->assertCount(1, $result->data());
        $this->assertCount(0, $result->logs());

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
        $this->assertCount(1, $result->data());
        $this->assertCount(0, $result->logs());
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
                ['path' => '0'],
                ['path' => '0'],
                ['path' => '0'],
                ['path' => '0'],
                ['path' => '0'],
                ['path' => '0'],
                ['path' => '0'],
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

    public function testLoginDuplicateValidation()
    {
        $source1 = [
            [
                'login' => 'test',
                'first_name' => 'Дмитрий',
                'last_name' => 'Иванов',
                'phone' => '79275000000',
                'email' => null,
                'groups' => [
                    ['path' => 'region'],
                    ['path' => 'city'],
                    ['path' => 'role'],
                    ['path' => 'position'],
                    ['path' => 'team'],
                    ['path' => 'department'],
                    ['path' => 'assignment'],
                ]
            ]
        ];
        $source2 =[
            [
                'login' => 'test',
                'first_name' => 'Андрей',
                'last_name' => 'Петров',
                'phone' => '79275000001',
                'email' => null,
                'groups' => [
                    ['path' => 'region'],
                    ['path' => 'city'],
                    ['path' => 'role'],
                    ['path' => 'position'],
                    ['path' => 'team'],
                    ['path' => 'department'],
                    ['path' => 'assignment'],
                ]
            ]
        ];

        $pipeline = new UserSyncPipelineData();
        $pipeline->addSource('source-1', $source1);
        $pipeline->addSource('source-2', $source2);

        $result = (new TypicalUserValidator())->validate($pipeline);
        $this->assertCount(1, $result->data());
        $this->assertCount(1, $result->logs());

        $error = $result->logs()[0]['errors'][0];
        $this->assertArrayHasKey('extra', $error);
        $this->assertEquals('Login already exists', $error['message']);
        $this->assertEquals('source-1', $error['extra']);
    }

    public function testInvalidGroupKey()
    {
        $users = [[
            'login' => 'test',
            'first_name' => 'Дмитрий',
            'last_name' => 'Иванов',
            'phone' => '79275000000',
            'email' => null,
            'groups' => [
                ['path' => '0'],
                ['path' => '0'],
                ['bad-key' => '0'],
            ]
        ]];
        $validator = new TypicalUserValidator();
        $result = $validator->validate($this->buildPipeline($users));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }

    public function testNotStringPathGroupKey()
    {
        $users = [[
            'login' => 'test',
            'first_name' => 'Дмитрий',
            'last_name' => 'Иванов',
            'phone' => '79275000000',
            'email' => null,
            'groups' => [
                ['path' => 100],
                ['path' => null],
                ['path' => '0'],
            ]
        ]];
        $validator = new TypicalUserValidator();
        $result = $validator->validate($this->buildPipeline($users));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }

    public function testNotIntIdGroupKey()
    {
        $users = [[
            'login' => 'test',
            'first_name' => 'Дмитрий',
            'last_name' => 'Иванов',
            'phone' => '79275000000',
            'email' => null,
            'groups' => [
                ['id' => '100'],
            ]
        ]];
        $validator = new TypicalUserValidator();
        $result = $validator->validate($this->buildPipeline($users));
        $this->assertCount(0, $result->data());
        $this->assertCount(1, $result->logs());
    }
}