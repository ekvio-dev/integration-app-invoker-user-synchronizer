<?php

declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserValidation;

use Ekvio\Integration\Contracts\User\UserData;

/**
 * Class ValidationResult
 * @package Ekvio\Integration\Invoker\UserValidation
 */
class UserValidationCollector
{
    private const ERROR_CODE = 1007;
    private const ERROR_STATUS = 'error';

    /**
     * @var array
     */
    private $valid = [];
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @param UserData $user
     */
    public function addValid(UserData $user): void
    {
        $this->valid[] = $user;
    }

    /**
     * @param string $index
     * @param string|null $login
     * @param string $field
     * @param string $message
     * @param string|null $extra
     */
    public function addError(string $index, ?string $login, string $field, string $message, ?string $extra = null): void
    {
        if (isset($this->errors[$index])) {
            $this->errors[$index]['errors'][] = [
                'code' => self::ERROR_CODE,
                'field' => $field,
                'message' => $message,
                'extra' => $extra
            ];
        } else {
            $this->errors[$index] = [
                'index' => $index,
                'login' => $login,
                'status' => self::ERROR_STATUS,
                'errors' => [
                    [
                        'code' => self::ERROR_CODE,
                        'field' => $field,
                        'message' => $message,
                        'extra' => $extra
                    ]
                ]
            ];
        }
    }

    /**
     * @return UserData[]
     */
    public function valid(): array
    {
        return $this->valid;
    }

    /**
     * @return array
     */
    public function errors(): array
    {
        return array_values($this->errors);
    }
}
