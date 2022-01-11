<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\Tests\Unit\Collector;

use Ekvio\Integration\Contracts\Extractor;
use Ekvio\Integration\Invoker\UserCollector\ExtractorPriorityCollector;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LightMemoryExtractor implements Extractor
{
    private $name;
    private $data;
    public function __construct(string $name, array $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function extract(array $options = []): array
    {
        return $this->data;
    }

    public function name(): string
    {
        return $this->name;
    }
}

class ExtractorPriorityCollectorTest extends TestCase
{
    public function testPrioritySourceNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not found prior extractor not-exist');

        $collector = new ExtractorPriorityCollector('not-exist', [
            'exist' => new LightMemoryExtractor('test', [])
        ]);
        $collector->collect();
    }

    public function testPrioritySourceNotExistIfInExclude()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not found prior extractor exist');

        $collector = new ExtractorPriorityCollector('exist', [
            'exist' => new LightMemoryExtractor('test', [])
        ], ['exclude' => ['exist']]);
        $collector->collect();
    }

    public function testCollectPipelineDateFromExtractors()
    {
        $collector = new ExtractorPriorityCollector('first', [
            'second' => new LightMemoryExtractor('second', [['login' => 'test-2-1'], ['login' => 'test-2-2']]),
            'first' => new LightMemoryExtractor('first', [['login' => 'test-1-1'], ['login' => 'test-1-2']]),
            'third' => new LightMemoryExtractor('third', [['login' => 'test-3-1'], ['login' => 'test-3-2']]),
        ], [
            'exclude' => ['third']
        ]);

        $pipeline = $collector->collect();
        $source = array_values($pipeline->sources());

        $this->assertEquals('first', $source[0]['name']);
        $this->assertEquals('second', $source[1]['name']);
        $this->assertCount(2, $source);
    }
}