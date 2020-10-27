<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Integration;

use Ekvio\Integration\Invoker\UserCollector\ExtractorPriorityCollector;
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
        $collector = new ExtractorPriorityCollector('hr', [
        'hr' => new UserMemoryExtractor,
    ]);

        $synchronizer = new UserSynchronizer(
            $collector,
            new TypicalUserFactory(),
            new TypicalUserValidator(),
            new UserApiDummy(),
            new DummyProfiler()
        );

        $result = $synchronizer();
        $this->assertCount(1, $result->data());
    }
}