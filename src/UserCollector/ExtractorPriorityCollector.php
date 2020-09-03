<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserCollector;

use Ekvio\Integration\Contracts\Collector;
use Ekvio\Integration\Contracts\Extractor;
use Ekvio\Integration\Invoker\UserSyncPipelineData;
use RuntimeException;
use Webmozart\Assert\Assert;

/**
 * Class ExtractorPriorityCollector
 * @package Ekvio\Integration\Invoker\UserCollector
 */
class ExtractorPriorityCollector implements Collector
{
    /**
     * @var string prior extractor name
     */
    private $priorExtractorName;
    /**
     * @var Extractor[]
     */
    private $extractors;

    /**
     * ExtractorPriorityCollector constructor.
     * @param string $priorExtractorName
     * @param Extractor[] $extractors
     */
    public function __construct(string $priorExtractorName, array $extractors)
    {
        Assert::notEmpty($priorExtractorName, 'Prior extractor name required');
        Assert::notEmpty($extractors, 'Extractors required');
        Assert::allIsInstanceOf($extractors, Extractor::class, sprintf('All extractors must implement %s', Extractor::class));

        $this->priorExtractorName = $priorExtractorName;
        $this->extractors = $extractors;
    }

    /**
     * @param array $options
     * @return UserSyncPipelineData
     */
    public function collect(array $options = [])
    {
        $sorted = [];
        foreach ($this->extractors as $index => $extractor) {
            if($extractor->name() === $this->priorExtractorName) {
                $sorted[] = $extractor;
            }
        }

        if(!$sorted) {
            throw new RuntimeException(sprintf('Not found prior extractor %s', $this->priorExtractorName));
        }

        foreach ($this->extractors as $extractor) {
            if($extractor->name() === $this->priorExtractorName) {
                continue;
            }
            $sorted[] = $extractor;
        }

        $userSyncPipelineData = new UserSyncPipelineData();
        foreach ($sorted as $extractor) {
            $userSyncPipelineData->addSource($extractor->name(), $extractor->extract());
        }

        return $userSyncPipelineData;
    }
}