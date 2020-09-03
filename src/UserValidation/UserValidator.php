<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserValidation;

use Ekvio\Integration\Contracts\User\UserPipelineData;

/**
 * Interface UserValidator
 * @package App
 */
interface UserValidator
{
    /**
     * @param UserPipelineData $users
     * @return UserPipelineData
     */
    public function validate(UserPipelineData $users): UserPipelineData;
}