<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Unit;

use Ekvio\Integration\Invoker\UserSyncPipelineData;
use PHPUnit\Framework\TestCase;

class UserSyncPipelineDataTest extends TestCase
{
    public function testAddSourceToPipeline()
    {
        $pipeline = new UserSyncPipelineData();
        $pipeline->addSource('test1', [[1], [2], [3]]);
        $pipeline->addSource('test2', [[4], [5]]);

        $this->assertCount(2, $pipeline->sources());
        $this->assertCount(5, $pipeline->data());
    }

    public function testLogsFromPipeline()
    {
        $pipeline = new UserSyncPipelineData();
        $pipeline->addLog([
            'index' => null,
            'status' => 'error',
            'login' => 'test',
            'errors' => []
        ]);
        $pipeline->addLog([
            'index' => '0_test',
            'status' => 'error',
            'errors' => [
                [
                    'code' => 1000,
                    'field' => 'first_name',
                    'message' => 'Bad value',
                    'extra' => null
                ]
            ]
        ]);
        $pipeline->addLog([
            'index' => '1_test',
            'status' => 'error',
            'errors' => [
                [
                    'code' => 1000,
                    'field' => 'last_name',
                    'message' => 'Bad value',
                    'extra' => null
                ]
            ]
        ]);
        $pipeline->addLog([
            'index' => '0_test',
            'status' => 'error',
            'errors' => [
                [
                    'code' => 1000,
                    'field' => 'last_name',
                    'message' => 'Bad value',
                    'extra' => null
                ]
            ]
        ]);
        $pipeline->addLog([
            'index' => '0_test',
            'status' => 'created'
        ]);

        $logs = $pipeline->logs();
        $this->assertCount(3, $logs);
        $this->assertEquals([
            'index' => '0_test',
            'status' => 'error',
            'errors' => [
                [
                    'code' => 1000,
                    'field' => 'first_name',
                    'message' => 'Bad value',
                    'extra' => null
                ],
                [
                    'code' => 1000,
                    'field' => 'last_name',
                    'message' => 'Bad value',
                    'extra' => null
                ]
            ]
        ], $logs[0]);
    }
}