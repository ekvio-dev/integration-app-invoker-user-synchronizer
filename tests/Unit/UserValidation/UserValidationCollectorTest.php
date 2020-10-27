<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Unit\UserValidation;

use Ekvio\Integration\Invoker\UserSyncData;
use Ekvio\Integration\Invoker\UserValidation\UserValidationCollector;
use PHPUnit\Framework\TestCase;

/**
 * Class UserValidationCollectorTest
 * @package Ekvio\Integration\Invoker\Tests\Unit\UserValidation
 */
class UserValidationCollectorTest extends TestCase
{
    /**
     * @var UserValidationCollector
     */
    private $collector;

    protected function setUp(): void
    {
        $this->collector = new UserValidationCollector();
        parent::setUp();
    }

    public function testUserValidationCollectorAddData()
    {
        $this->collector->addValid(UserSyncData::fromData('1', ['login' => 'ivanov.i']));
        $this->collector->addError('5', null, 'login', 'Login required');
        $this->collector->addError('6', 'test', 'phone', 'Invalid phone');

        $this->assertCount(1, $this->collector->valid());
        $this->assertCount(2, $this->collector->errors());
    }

    public function testUserValidationCollectorErrorStructure()
    {
        $this->collector->addError('1', 'test', 'phone', 'Invalid phone');
        $this->collector->addError('1', 'test', 'phone', 'Bad count symbols');

        $error = $this->collector->errors()[0];
        $keys = array_keys($error);
        $this->assertEquals(
            ['index', 'login', 'status', 'errors'],
            $keys
        );

        $this->assertEquals(
            [
                'index' => '1',
                'login' => 'test',
                'status' => 'error',
                'errors' => [
                    ['code' => 1007, 'field' => 'phone', 'message' => 'Invalid phone'],
                    ['code' => 1007, 'field' => 'phone', 'message' => 'Bad count symbols'],
                ]
            ],
            $error
        );
    }
}