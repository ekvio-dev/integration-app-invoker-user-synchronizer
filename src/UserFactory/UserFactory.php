<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserFactory;

/**
 * Interface UserFactory
 * @package App
 */
interface UserFactory
{
    /**
     * @param array $users
     * @return array
     */
    public function build(array $users): array;
}