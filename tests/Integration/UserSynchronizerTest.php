<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Integration;

use Ekvio\Integration\Invoker\UserFactory\TypicalUserFactory;
use Ekvio\Integration\Invoker\UserSynchronizer;
use Ekvio\Integration\Invoker\UserValidation\TypicalUserValidator;
use PHPUnit\Framework\TestCase;

/**
 * Class UserSynchronizerTest
 * @package Ekvio\Integration\Invoker\Tests\Integration
 */
class UserSynchronizerTest extends TestCase
{
    public function testIntegrationUsageUserSynchronizer()
    {
        $synchronizer = new UserSynchronizer(
            new UserMemoryExtractor,
            new TypicalUserFactory(),
            new TypicalUserValidator(),
            new UserApiDummy(),
            new DummyProfiler()
        );

        $log = $synchronizer();
        $this->assertCount(4, $log);
    }
}