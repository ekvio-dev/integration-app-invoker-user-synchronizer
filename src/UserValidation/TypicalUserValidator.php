<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserValidation;

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
     * @var callable
     */
    private $loginValidator;
    /**
     * @var callable
     */
    private $firstNameValidator;
    /**
     * @var callable
     */
    private $lastNameValidator;
    /**
     * @var callable
     */
    private $phoneValidator;
    /**
     * @var callable
     */
    private $emailValidator;
    /**
     * @var callable
     */
    private $groupsValidator;

    /**
     * TypicalUserValidator constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if(isset($options['loginValidator']) && is_callable($options['loginValidator'])) {
            $this->loginValidator = $options['loginValidator'];
        }

        if(isset($options['firstNameValidator']) && is_callable($options['firstNameValidator'])) {
            $this->firstNameValidator = $options['firstNameValidator'];
        }

        if(isset($options['lastNameValidator']) && is_callable($options['lastNameValidator'])) {
            $this->lastNameValidator = $options['lastNameValidator'];
        }

        if(isset($options['phoneValidator']) && is_callable($options['phoneValidator'])) {
            $this->phoneValidator = $options['phoneValidator'];
        }

        if(isset($options['emailValidator']) && is_callable($options['emailValidator'])) {
            $this->emailValidator = $options['emailValidator'];
        }

        if(isset($options['groupsValidator']) && is_callable($options['groupsValidator'])) {
            $this->groupsValidator = $options['groupsValidator'];
        }
    }
    /**
     * @param array $users
     * @return UserValidationCollector
     */
    public function validate(array $users): UserValidationCollector
    {
        $this->validationCollector = new UserValidationCollector();

        foreach ($users as $index => $user) {
            $this->validateUser($index, $user);
        }

        return $this->validationCollector;
    }

    /**
     * @param int $index
     * @param array $user
     */
    protected function validateUser(int $index, array $user): void
    {
        $isValid = true;
        $isValid = $this->loginValidator
            ? (bool)($this->loginValidator)($index, $user, $this->validationCollector)
            : $this->isValidLogin($index, $user);


        $isValid = $this->firstNameValidator
            ? (bool)($this->firstNameValidator)($index, $user, $this->validationCollector)
            : $this->isValidFirstName($index, $user);

        $isValid = $this->lastNameValidator
            ? (bool)($this->lastNameValidator)($index, $user, $this->validationCollector)
            : $this->isValidLastName($index, $user);

        $isValid = $this->phoneValidator
            ? (bool)($this->phoneValidator)($index, $user, $this->validationCollector)
            : $this->isValidPhone($index, $user);

        $isValid = $this->emailValidator
            ? (bool)($this->emailValidator)($index, $user, $this->validationCollector)
            : $this->isValidEmail($index, $user);

        $isValid = $this->groupsValidator
            ? (bool)($this->groupsValidator)($index, $user, $this->groupsValidator)
            : $this->isValidGroups($index, $user);

        if($isValid) {
            $this->validationCollector->addValid($user);
        }
    }

    /**
     * @param int $index
     * @param array $user
     * @return bool
     */
    protected function isValidLogin(int $index, array $user): bool
    {
        if(empty($user['login'])) {
            $this->validationCollector->addError($index, null, 'login', 'Login required');
            return false;
        }

        return true;
    }

    /**
     * @param int $index
     * @param array $user
     * @return bool
     */
    protected function isValidFirstName(int $index, array $user): bool
    {
        $key = 'first_name';
        $login = $this->getLogin($user);
        if(empty($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'First name required');
            return false;
        }

        if(!$this->isCyrillic($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'First name must be cyrillic');
            return false;
        }

        return true;
    }

    /**
     * @param int $index
     * @param array $user
     * @return bool
     */
    protected function isValidLastName(int $index, array $user): bool
    {
        $key = 'last_name';
        $login = $this->getLogin($user);
        if(empty($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'Last name required');
            return false;
        }

        if(!$this->isCyrillic($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'Last name must be cyrillic');
            return false;
        }

        return true;
    }

    /**
     * @param int $index
     * @param array $user
     * @return bool
     */
    protected function isValidPhone(int $index, array $user): bool
    {
        $key = 'phone';
        $login = $this->getLogin($user);

        if (empty($user[$key])) {
            $this->validationCollector->addError($index, $login, $key, 'Phone required');
            return false;
        }

        $phone = $user[$key];
        if(!is_numeric($phone)) {
            $this->validationCollector->addError($index, $login, $key, 'Phone is required only numbers');
            return false;
        }

        $length = strlen($phone, 'UTF-8');
        if ($length < 10) {
            $this->validationCollector->addError($index, $login, $key, 'Phone number must be min 10 numbers');
            return false;
        }

        if($length > 16) {
            $this->validationCollector->addError($index, $login, $key, 'Phone number must be max 16 numbers');
            return false;
        }

        return true;
    }

    /**
     * @param int $index
     * @param array $user
     * @return bool
     */
    protected function isValidEmail(int $index, array $user): bool
    {
        $key = 'email';
        $login = $this->getLogin($user);

        if(!array_key_exists($key, $user)) {
            $this->validationCollector->addError($index, $login, $key, 'Email required');
            return false;
        }

        //email is optional
        if(empty($user[$key])) {
            return true;
        }

        if(!filter_var($user[$key], FILTER_VALIDATE_EMAIL)) {
            $this->validationCollector->addError($index, $login, $key, 'Email is not valid');
            return false;
        }

        return true;
    }

    /**
     * @param int $index
     * @param array $user
     * @return bool
     */
    protected function isValidGroups(int $index, array $user): bool
    {
        $isGroupValid = true;
        $login = $this->getLogin($user);
        foreach (self::GROUPS as $requiredGroup) {
            $group = $user['groups'][$requiredGroup] ?? null;
            if(empty($group)) {
                $this->validationCollector->addError($index, $login,'groups', sprintf('Group %s is required and not blank', $requiredGroup));
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