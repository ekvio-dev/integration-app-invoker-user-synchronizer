<?php

declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserValidation;

use Closure;
use Ekvio\Integration\Contracts\User\UserData;
use Ekvio\Integration\Contracts\User\UserPipelineData;
use Ekvio\Integration\Invoker\UserSyncPipelineData;

/**
 * Class TypicalUserValidator
 * @package Ekvio\Integration\Invoker\UserValidation
 */
class TypicalUserValidator implements UserValidator
{
    private const GROUPS = ['region', 'city', 'role', 'position', 'team', 'department', 'assignment'];
    /**
     * @var UserValidationCollector
     */
    private $validationCollector;
    /**
     * @var Closure
     */
    private $loginValidator;
    /**
     * @var array
     */
    private $loginCollection = [];
    /**
     * @var Closure
     */
    private $loginCollectionValidator;
    /**
     * @var Closure
     */
    private $firstNameValidator;
    /**
     * @var Closure
     */
    private $lastNameValidator;
    /**
     * @var Closure
     */
    private $phoneValidator;
    /**
     * @var Closure
     */
    private $emailValidator;
    /**
     * @var Closure
     */
    private $groupsValidator;

    /**
     * TypicalUserValidator constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['loginCollectionValidator']) && is_callable($options['loginCollectionValidator'])) {
            $this->loginCollectionValidator = $options['loginCollectionValidator'];
        }

        if (isset($options['loginValidator']) && is_callable($options['loginValidator'])) {
            $this->loginValidator = $options['loginValidator'];
        }

        if (isset($options['firstNameValidator']) && is_callable($options['firstNameValidator'])) {
            $this->firstNameValidator = $options['firstNameValidator'];
        }

        if (isset($options['lastNameValidator']) && is_callable($options['lastNameValidator'])) {
            $this->lastNameValidator = $options['lastNameValidator'];
        }

        if (isset($options['phoneValidator']) && is_callable($options['phoneValidator'])) {
            $this->phoneValidator = $options['phoneValidator'];
        }

        if (isset($options['emailValidator']) && is_callable($options['emailValidator'])) {
            $this->emailValidator = $options['emailValidator'];
        }

        if (isset($options['groupsValidator']) && is_callable($options['groupsValidator'])) {
            $this->groupsValidator = $options['groupsValidator'];
        }
    }

    /**
     * @param UserPipelineData $pipelineData
     * @return UserSyncPipelineData
     */
    public function validate(UserPipelineData $pipelineData): UserPipelineData
    {
        $this->loginCollection = [];
        $this->validationCollector = new UserValidationCollector();

        foreach ($pipelineData->data() as $userData) {
            $this->validateUser($userData);
        }

        $pipelineData = $pipelineData->change($this->validationCollector->valid());

        foreach ($this->validationCollector->errors() as $error) {
            $pipelineData->addLog($error);
        }

        return $pipelineData;
    }

    /**
     * @param UserData $userData
     */
    protected function validateUser(UserData $userData): void
    {
        $index = $userData->key();
        $user = $userData->data();

        $results[] = $this->loginValidator
            ? (bool) $this->loginValidator->call($this, $index, $user)
            : $this->isValidLogin($index, $user);

        $results[] = $this->loginCollectionValidator
            ? (bool) $this->loginCollectionValidator->call($this, $index, $user)
            : $this->isLoginAlreadyExist($index, $user);

        $results[] = $this->firstNameValidator
            ? (bool) $this->firstNameValidator->call($this, $index, $user)
            : $this->isValidFirstName($index, $user);

        $results[] = $this->lastNameValidator
            ? (bool) $this->lastNameValidator->call($this, $index, $user)
            : $this->isValidLastName($index, $user);

        $results[] = $this->phoneValidator
            ? (bool) $this->phoneValidator->call($this, $index, $user)
            : $this->isValidPhone($index, $user);

        $results[] = $this->emailValidator
            ? (bool) $this->emailValidator->call($this, $index, $user)
            : $this->isValidEmail($index, $user);

        $results[] = $this->groupsValidator
            ? (bool)($this->groupsValidator)($index, $user, $this->validationCollector)
            : $this->isValidGroups($index, $user);

        if ($this->isValid($results)) {
            $this->validationCollector->addValid($userData);
            $this->loginCollection[] = $user['login'];
        }
    }

    /**
     * @param array $results
     * @return bool
     */
    protected function isValid(array $results): bool
    {
        foreach ($results as $result) {
            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidLogin(string $index, array $user): bool
    {
        if (empty($user['login'])) {
            $this->validationCollector->addError($index, null, 'login', 'Login required');
            return false;
        }

        return true;
    }


    /**
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isLoginAlreadyExist(string $index, array $user): bool
    {
        if (!empty($user['login']) && in_array($user['login'], $this->loginCollection, true)) {
            $this->validationCollector->addError($index, $user['login'], 'login', 'Login already exists');
            return false;
        }

        return true;
    }

    /**
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidFirstName(string $index, array $user): bool
    {
        $key = 'first_name';
        $login = $this->getLogin($user);
        if (empty($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'First name required');
            return false;
        }

        if (!$this->isCyrillic($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'First name must be cyrillic');
            return false;
        }

        return true;
    }

    /**
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidLastName(string $index, array $user): bool
    {
        $key = 'last_name';
        $login = $this->getLogin($user);
        if (empty($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'Last name required');
            return false;
        }

        if (!$this->isCyrillic($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'Last name must be cyrillic');
            return false;
        }

        return true;
    }

    /**
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidPhone(string $index, array $user): bool
    {
        $key = 'phone';
        $login = $this->getLogin($user);

        if (empty($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'Phone required');
            return false;
        }

        $phone = $user[$key];
        if (!is_numeric($phone)) {
            $this->validationCollector->addError($index, $login, $key, 'Phone is required only numbers');
            return false;
        }

        $length = strlen($phone);
        if ($length < 10) {
            $this->validationCollector->addError($index, $login, $key, 'Phone number must be min 10 numbers');
            return false;
        }

        if ($length > 16) {
            $this->validationCollector->addError($index, $login, $key, 'Phone number must be max 16 numbers');
            return false;
        }

        return true;
    }

    /**
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidEmail(string $index, array $user): bool
    {
        $key = 'email';
        $login = $this->getLogin($user);

        if (!array_key_exists($key, $user)) {
            $this->validationCollector->addError($index, $login, $key, 'Email required');
            return false;
        }

        //email is optional
        if (empty($user[$key])) {
            return true;
        }

        if (!filter_var($user[$key], FILTER_VALIDATE_EMAIL)) {
            $this->validationCollector->addError($index, $login, $key, 'Email is not valid');
            return false;
        }

        return true;
    }

    /**
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidGroups(string $index, array $user): bool
    {
        $isGroupValid = true;
        $login = $this->getLogin($user);
        foreach (self::GROUPS as $requiredGroup) {
            $group = $user['groups'][$requiredGroup] ?? null;
            if (empty($group)) {
                $this->validationCollector->addError(
                    $index,
                    $login,
                    'groups',
                    sprintf('Group %s is required and not blank', $requiredGroup)
                );
                $isGroupValid = false;
            }
        }

        return $isGroupValid;
    }

    /**
     * @param $string
     * @return bool
     */
    protected function isCyrillic($string): bool
    {
        return (bool) preg_match('/^[\sа-яА-ЯёЁ-]+$/u', $string);
    }

    /**
     * @param array $user
     * @return string|null
     */
    protected function getLogin(array $user): ?string
    {
        return $user['login'] ?? null;
    }
}
