<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserValidation;

/**
 * Interface UserValidator
 * @package App
 */
interface UserValidator
{
    /**
     * @param array $users
     * @return UserValidationCollector
     */
    public function validate(array $users): UserValidationCollector;
}