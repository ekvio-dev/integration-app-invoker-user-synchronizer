<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserFactory;

use Ekvio\Integration\Contracts\User\UserPipelineData;

/**
 * Interface UserFactory
 * @package App
 */
interface UserFactory
{
    /**
     * @param UserPipelineData $data
     * @return UserPipelineData
     */
    public function build(UserPipelineData $data): UserPipelineData;
}