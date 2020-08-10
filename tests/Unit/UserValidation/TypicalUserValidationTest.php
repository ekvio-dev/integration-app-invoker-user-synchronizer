<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Unit\UserValidation;

use Ekvio\Integration\Invoker\UserValidation\TypicalUserValidator;
use Ekvio\Integration\Invoker\UserValidation\UserValidationCollector;
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

    public function testSuccessUserValidation()
    {
        $validator = new TypicalUserValidator();

        $result = $validator->validate([$this->user()]);
        $this->assertCount(1, $result->valid());
        $this->assertCount(0, $result->errors());
    }

    public function testFalseUserLoginValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['login'] = null;

        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());
    }

    public function testFalseFirstNameValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['first_name'] = null;

        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());

        $user['first_name'] = 'Ivanov';
        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());
    }

    public function testFalseLastNameValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['last_name'] = null;

        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());

        $user['last_name'] = 'Ivan';
        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());
    }

    public function testFalsePhoneValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();
        $user['phone'] = null;

        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());

        $user['phone'] = 'n79275200000';
        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());

        $user['phone'] = '123456789';
        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());

        $user['phone'] = '12345678910111213';
        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());
    }

    public function testFalseEmailValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();

        $user['email'] = 'not-valid-email';
        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());
    }

    public function testSuccessEmailValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();

        $user['email'] = 'test@test.ru';
        $result = $validator->validate([$user]);
        $this->assertCount(1, $result->valid());
        $this->assertCount(0, $result->errors());
    }

    public function testFalseUserGroupsValidation()
    {
        $validator = new TypicalUserValidator();
        $user = $this->user();

        unset($user['groups']['region']);
        $result = $validator->validate([$user]);
        $this->assertCount(0, $result->valid());
        $this->assertCount(1, $result->errors());
    }

    public function testUserValidationModifications()
    {
        $validator = new TypicalUserValidator([
            'loginValidator' => static function(int $index, array $user, UserValidationCollector $collector) {
                return true;
            },
            'firstNameValidator' => static function(int $index, array $user, UserValidationCollector $collector) {
                return true;
            },
            'lastNameValidator' => static function(int $index, array $user, UserValidationCollector $collector) {
                return true;
            },
            'phoneValidator' => static function(int $index, array $user, UserValidationCollector $collector) {
                return true;
            },
            'emailValidator' => static function(int $index, array $user, UserValidationCollector $collector) {
                return true;
            },
            'groupsValidator' => static function(int $index, array $user, UserValidationCollector $collector) {
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

        $result = $validator->validate([$user]);
        $this->assertCount(1, $result->valid());
        $this->assertCount(0, $result->errors());
    }
}