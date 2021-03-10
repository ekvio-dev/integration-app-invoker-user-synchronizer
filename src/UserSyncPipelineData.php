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
    private const DELIMITER = '___';
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
     * @var string
     */
    private $keyDelimiter;

    /**
     * UserSyncPipelineData constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->keyDelimiter = $options['keyDelimiter'] ?? self::DELIMITER;
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
     * @param string $name
     * @param array $data
     */
    public function addSource(string $name, array $data): void
    {
        $key = sprintf('%s_%s', count($this->sources), substr(md5($name), 0, 6));

        if (isset($this->sources[$key])) {
            throw new RuntimeException(sprintf('Source with name %s already exists', $name));
        }

        $this->sources[$key] = [
            'name' => $name,
            'data' => $data
        ];

        foreach ($data as $index => $value) {
            $dataKey = sprintf('%s%s%s', $key, $this->keyDelimiter, $index);
            $this->data[] = UserSyncData::fromData($dataKey, $value);
        }
    }

    /**
     * @param array $log
     */
    public function addLog(array $log): void
    {
        $index = $log['index'] ?? null;
        $status = $log['status'] ?? null;
        if(!$index || !$status) {
            return;
        }

        $hash = $status . '_' . $index;
        if(!isset($this->log[$hash])) {
            $this->log[$hash] = $log;
            return;
        }

        if($status === LogStatus::ERROR) {
            $this->log[$hash]['errors'] = array_merge($this->log[$hash]['errors'], $log['errors']);
        }
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
        return array_values($this->log);
    }

    /**
     * @return array
     */
    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * @param string $key
     * @return bool
     */
    private function exists(string $key): bool
    {
        [$source, $index] = explode($this->keyDelimiter, $key);
        if (!$source || !is_numeric($index)) {
            throw new RuntimeException(
                sprintf('Bad structure UserData key, source (%s) or index (%s)', $source, $index)
            );
        }

        return isset($this->sources[$source]['data'][$index]);
    }

    /**
     * @param string $key
     * @return array
     */
    public function dataFromSource(string $key): array
    {
        if (strpos($key, $this->keyDelimiter) === false) {
            throw new RuntimeException(sprintf('Invalid data key %s', $key));
        }

        [$sourceKey, $dataKey] = explode($this->keyDelimiter, $key);

        if (!isset($this->sources[$sourceKey]['data'][(int) $dataKey])) {
            throw new RuntimeException(sprintf('Source data with key %s not found', $key));
        }

        return $this->sources[$sourceKey]['data'][(int) $dataKey];
    }

    /**
     * @param string $key
     * @return string
     */
    public function sourceName(string $key): string
    {
        if (strpos($key, $this->keyDelimiter) !== false) {
            [$key, ] = explode($this->keyDelimiter, $key);
        }

        if (!isset($this->sources[$key])) {
            throw new RuntimeException(sprintf('Source with key %s not found', $key));
        }

        return $this->sources[$key]['name'];
    }
}
