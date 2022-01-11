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
    private $options = [];

    /**
     * ExtractorPriorityCollector constructor.
     * @param string $priorExtractorName
     * @param Extractor[] $extractors
     */
    public function __construct(string $priorExtractorName, array $extractors, array $options = [])
    {
        Assert::notEmpty($priorExtractorName, 'Prior extractor name required');
        Assert::notEmpty($extractors, 'Extractors required');
        Assert::allIsInstanceOf(
            $extractors,
            Extractor::class,
            sprintf('All extractors must implement %s', Extractor::class)
        );

        $this->priorExtractorName = $priorExtractorName;
        $this->extractors = $extractors;
        $this->options = $options;
    }

    /**
     * @param array $options
     * @return UserSyncPipelineData
     */
    public function collect(array $options = [])
    {
        $sorted = [];
        $exclude = (array) $this->options['exclude'] ?? [];

        foreach ($this->extractors as $name => $extractor) {

            if(in_array($name, $exclude, true)) {
                continue;
            }

            if ($name === $this->priorExtractorName) {
                $sorted[$name] = $extractor;
            }
        }

        if (!$sorted) {
            throw new RuntimeException(sprintf('Not found prior extractor %s', $this->priorExtractorName));
        }

        foreach ($this->extractors as $name => $extractor) {
            if ($name === $this->priorExtractorName) {
                continue;
            }

            if(in_array($name, $exclude, true)) {
                continue;
            }

            $sorted[$name] = $extractor;
        }

        $userSyncPipelineData = new UserSyncPipelineData();
        foreach ($sorted as $name => $extractor) {
            $userSyncPipelineData->addSource($name, $extractor->extract());
        }

        return $userSyncPipelineData;
    }
}
