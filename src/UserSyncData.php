<?php

declare(strict_types=1);

namespace Ekvio\Integration\Invoker;

use Ekvio\Integration\Contracts\User\UserData;

/**
 * Class UserData
 * @package Ekvio\Integration\Invoker
 */
class UserSyncData implements UserData
{
    /**
     * @var string
     */
    private $key;
    /**
     * @var array
     */
    private $data;

    /**
     * UserData constructor.
     */

    private function __construct()
    {
    }

    /**
     * UserData named constructor
     * @param string $key
     * @param array $data
     * @return UserSyncData
     */
    public static function fromData(string $key, array $data): self
    {
        $self = new self();
        $self->key = $key;
        $self->data = $data;

        return $self;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }
}
