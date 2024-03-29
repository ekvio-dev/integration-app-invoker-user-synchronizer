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
    protected const GROUPS = ['region', 'city', 'role', 'position', 'team', 'department', 'assignment'];
    protected const GROUP_KEYS = ['path'];
    /**
     * @var UserValidationCollector
     */
    protected $validationCollector;
    /**
     * @var Closure
     */
    protected $loginValidator;
    /**
     * @var array
     */
    protected $loginCollection = [];
    /**
     * @var Closure
     */
    protected $loginCollectionValidator;
    /**
     * @var Closure
     */
    protected $firstNameValidator;
    /**
     * @var Closure
     */
    protected $lastNameValidator;
    /**
     * @var Closure
     */
    protected $phoneValidator;
    /**
     * @var Closure
     */
    protected $emailValidator;
    /**
     * @var Closure
     */
    protected $chiefEmailValidator;
    /**
     * @var Closure
     */
    protected $groupsValidator;

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

        if(isset($options['chiefEmailValidator']) && is_callable($options['chiefEmailValidator'])) {
            $this->chiefEmailValidator = $options['chiefEmailValidator'];
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
            /** @var UserData $userData */
            $source = $pipelineData->sourceName($userData->key());
            $this->validateUser($source, $userData);
        }

        $pipelineData = $pipelineData->change($this->validationCollector->valid());

        foreach ($this->validationCollector->errors() as $error) {
            $pipelineData->addLog($error);
        }

        return $pipelineData;
    }

    /**
     * @param string $source
     * @param UserData $userData
     */
    protected function validateUser(string $source, UserData $userData): void
    {
        $index = $userData->key();
        $user = $userData->data();

        $results[] = $this->loginValidator
            ? (bool) $this->loginValidator->call($this, $source, $index, $user)
            : $this->isValidLogin($source, $index, $user);

        $results[] = $this->loginCollectionValidator
            ? (bool) $this->loginCollectionValidator->call($this, $source, $index, $user)
            : $this->isLoginAlreadyExist($source, $index, $user);

        $results[] = $this->firstNameValidator
            ? (bool) $this->firstNameValidator->call($this, $source, $index, $user)
            : $this->isValidFirstName($source, $index, $user);

        $results[] = $this->lastNameValidator
            ? (bool) $this->lastNameValidator->call($this, $source, $index, $user)
            : $this->isValidLastName($source, $index, $user);

        $results[] = $this->phoneValidator
            ? (bool) $this->phoneValidator->call($this, $source, $index, $user)
            : $this->isValidPhone($source, $index, $user);

        $results[] = $this->emailValidator
            ? (bool) $this->emailValidator->call($this, $source, $index, $user)
            : $this->isValidEmail($source, $index, $user);

        $results[] = $this->chiefEmailValidator
            ? (bool) $this->chiefEmailValidator->call($this, $source, $index, $user)
            : $this->isValidChiefEmail($source, $index, $user);

        $results[] = $this->groupsValidator
            ? (bool) $this->groupsValidator->call($this, $source, $index, $user)
            : $this->isValidGroups($source, $index, $user);

        if ($this->isValid($results)) {
            $this->validationCollector->addValid($userData);
            $this->loginCollection[$user['login']] = $source;
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
     * @param string $source
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidLogin(string $source, string $index, array $user): bool
    {
        if (empty($user['login'])) {
            $this->validationCollector->addError($index, null, 'login', 'Login required');
            return false;
        }

        return true;
    }


    /**
     * @param string $source
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isLoginAlreadyExist(string $source, string $index, array $user): bool
    {
        if (!empty($user['login']) && isset($this->loginCollection[$user['login']])) {
            $originalSource = $this->loginCollection[$user['login']];
            $this->validationCollector->addError(
                $index, $user['login'], 'login', 'Login already exists', $originalSource);
            return false;
        }

        return true;
    }

    /**
     * @param string $source
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidFirstName(string $source, string $index, array $user): bool
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
     * @param string $source
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidLastName(string $source, string $index, array $user): bool
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
     * @param string $source
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidPhone(string $source, string $index, array $user): bool
    {
        $key = 'phone';
        $login = $this->getLogin($user);

        if (empty($user[$key])) {
            return true;
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
     * @param string $source
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidEmail(string $source, string $index, array $user): bool
    {
        $key = 'email';
        $login = $this->getLogin($user);

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

    protected function isValidChiefEmail(string $source, string $index, array $user): bool
    {
        $key = 'chief_email';
        $login = $this->getLogin($user);

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
     * @param string $source
     * @param string $index
     * @param array $user
     * @return bool
     */
    protected function isValidGroups(string $source, string $index, array $user): bool
    {
        $isGroupValid = true;
        $login = $this->getLogin($user);
        $groups = $user['groups'] ?? [];

        if(!$groups) {
            return true;
        }

        foreach ($groups as $key => $group) {
            $groupKey = key($group);

            if(!in_array($groupKey, self::GROUP_KEYS, true)) {
                $this->validationCollector->addError($index, $login, 'groups',
                    sprintf('Group key must be path. Group index: %s', $key));
                $isGroupValid = false;
                continue;
            }

            $value = $group[$groupKey];
            if($groupKey === 'path') {
                if(!is_string($value)) {
                    $this->validationCollector->addError($index, $login, 'groups',
                        sprintf('Group value must be string. Group index: %s', $key));
                    $isGroupValid = false;
                    continue;
                }

                if(mb_strlen(trim($value)) === 0) {
                    $this->validationCollector->addError($index, $login, 'groups',
                        sprintf('Group value is empty. Group index: %s', $key));
                    $isGroupValid = false;
                }
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
