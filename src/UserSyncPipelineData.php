<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker;

use Ekvio\Integration\Contracts\User\UserData;
use Ekvio\Integration\Contracts\User\UserPipelineData;
use RuntimeException;

/**
 * Class UserPipelineData
 * @package Ekvio\Integration\Invoker
 */
class UserSyncPipelineData implements UserPipelineData
{
    /**
     * @var array
     */
    private $sources = [];
    /**
     * @var array
     */
    private $data = [];
    /**
     * @var array
     */
    private $log = [];

    /**
     * @var callable
     */
    private $keyBuilder;

    /**
     * UserSyncPipelineData constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if(isset($options['keyBuilder']) && is_callable($options['keyBuilder'])) {
            $this->keyBuilder = $options['keyBuilder'];
        }
    }

    /**
     * @param UserData[] $usersData
     * @return UserPipelineData
     */
    public function change(array $usersData): UserPipelineData
    {
        foreach ($usersData as $userData) {
            $this->exists($userData->key());
        }

        $self = new self();
        $self->sources = $this->sources;
        $self->log = $this->log;
        $self->data = $usersData;

        return $self;
    }

    /**
     * @param string $key
     * @param array $data
     */
    public function addSource(string $key, array $data): void
    {
        if(isset($this->sources[$key])) {
            throw new RuntimeException(sprintf('Source key %s already exists', $key));
        }

        $this->sources[$key] = $data;
        foreach ($data as $index => $value) {
            $dKey = $this->buildKey($key, $index);
            $this->data[] = UserSyncData::fromData($dKey, $value);
        }
    }

    /**
     * @param array $log
     */
    public function addLog(array $log): void
    {
        $this->log[] = $log;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function logs(): array
    {
        return $this->log;
    }

    /**
     * @return array
     */
    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * @param string $extractorName
     * @param int $index
     * @return string
     */
    private function buildKey(string $extractorName, int $index): string
    {
        if($this->keyBuilder) {
            return ($this->keyBuilder)($extractorName, $index);
        }

        return sprintf('%s_%s', $extractorName, $index);
    }

    /**
     * @param string $key
     * @return bool
     */
    private function exists(string $key): bool
    {
        [$source, $index] = explode('_', $key);
        if(!$source || !is_numeric($index)) {
            throw new RuntimeException(sprintf('Bad structure UserData key, source (%s) or index (%s)', $source, $index));
        }

        return isset($this->sources[$source][$index]);
    }
}