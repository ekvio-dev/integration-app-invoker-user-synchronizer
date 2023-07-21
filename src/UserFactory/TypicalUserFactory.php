<?php

declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserFactory;

use Closure;
use Ekvio\Integration\Contracts\User\UserData;
use Ekvio\Integration\Contracts\User\UserPipelineData;
use Ekvio\Integration\Invoker\UserSyncData;

/**
 * Class TypicalUserFactory
 * @package App
 */
class TypicalUserFactory implements UserFactory
{
    protected const DEFAULT_FORM_VALUE = null;
    protected const USER_ACTIVE = '0';
    /**
     * @var array
     */
    private $attributes = [
        'login' => 'USR_LOGIN',
        'first_name' => 'USR_FIRST_NAME',
        'last_name' => 'USR_LAST_NAME',
        'phone' => 'USR_MOBILE',
        'email' => 'USR_EMAIL',
        'password' => 'PASSWORD',
        'chief_email' => 'MANAGER_EMAIL',
        'status' => 'USR_UDF_USER_FIRED',
        'groups' => [
            'region' => 'REGION_NAME',
            'role' => 'ROLE',
            'position' => 'POSITION_NAME',
            'team' => 'TEAM_NAME',
            'department' => 'DEPARTAMENT_NAME',
            'assignment' => 'ASSIGNMENT_NAME'
        ]
    ];

    private $groupDefaults = [];

    /**
     * @var array
     */
    private $forms = [];

    /**
     * @var Closure
     */
    private $buildForms;

    /**
     * @var Closure
     */
    private $beforeBuild;

    /**
     * @var Closure
     */
    private $beforeUserBuild;

    /**
     * @var Closure
     */
    private $afterBuild;

    /**
     * @var Closure
     */
    private $afterUserBuild;

    /**
     * @var Closure
     */
    private $loginBuilder;
    /**
     * @var Closure
     */
    private $firstNameBuilder;
    /**
     * @var Closure
     */
    private $lastNameBuilder;

    /**
     * @var Closure
     */
    private $emailBuilder;
    /**
     * @var Closure
     */
    private $phoneBuilder;
    /**
     * @var Closure
     */
    private $passwordBuilder;
    /**
     * @var Closure
     */
    private $chiefEmailBuilder;
    /**
     * @var Closure
     */
    private $statusBuilder;
    /**
     * @var Closure
     */
    private $groupsBuilder;
    /**
     * @var string
     */
    private $activeStatus = self::USER_ACTIVE;

    /**
     * @var bool
     */
    private $useGroupDefaults = false;

    /**
     * TypicalUserFactory constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['attributes'])) {
            $this->attributes = array_merge($this->attributes, $options['attributes']);
        }

        if (isset($options['forms'])) {
            $this->forms = $options['forms'];
        }

        if (isset($options['beforeBuild']) && is_callable($options['beforeBuild'])) {
            $this->beforeBuild = $options['beforeBuild'];
        }

        if (isset($options['beforeUserBuild']) && is_callable($options['beforeUserBuild'])) {
            $this->beforeUserBuild = $options['beforeUserBuild'];
        }

        if (isset($options['afterBuild']) && is_callable($options['afterBuild'])) {
            $this->afterBuild = $options['afterBuild'];
        }

        if (isset($options['afterUserBuild']) && is_callable($options['afterUserBuild'])) {
            $this->afterUserBuild = $options['afterUserBuild'];
        }

        if (isset($options['loginBuilder']) && is_callable($options['loginBuilder'])) {
            $this->loginBuilder = $options['loginBuilder'];
        }

        if (isset($options['firstNameBuilder']) && is_callable($options['firstNameBuilder'])) {
            $this->firstNameBuilder = $options['firstNameBuilder'];
        }

        if (isset($options['lastNameBuilder']) && is_callable($options['lastNameBuilder'])) {
            $this->lastNameBuilder = $options['lastNameBuilder'];
        }

        if (isset($options['emailBuilder']) && is_callable($options['emailBuilder'])) {
            $this->emailBuilder = $options['emailBuilder'];
        }

        if (isset($options['phoneBuilder']) && is_callable($options['phoneBuilder'])) {
            $this->phoneBuilder = $options['phoneBuilder'];
        }

        if (isset($options['passwordBuilder']) && is_callable($options['passwordBuilder'])) {
            $this->passwordBuilder = $options['passwordBuilder'];
        }

        if (isset($options['chiefEmailBuilder']) && is_callable($options['chiefEmailBuilder'])) {
            $this->chiefEmailBuilder = $options['chiefEmailBuilder'];
        }

        if (isset($options['statusBuilder']) && is_callable($options['statusBuilder'])) {
            $this->statusBuilder = $options['statusBuilder'];
        }

        if(isset($options['groupsBuilder']) && is_callable($options['groupsBuilder'])) {
            $this->groupsBuilder = $options['groupsBuilder'];
        }

        if(isset($options['buildForms']) && is_callable($options['buildForms'])) {
            $this->buildForms = $options['buildForms'];
        }

        if (isset($options['forms']) && is_array($options['forms'])) {
            $this->forms = $options['forms'];
        }

        if (isset($options['groupDefaults']) && is_array($options['groupDefaults'])) {
            $this->groupDefaults = $options['groupDefaults'];
        }

        if (isset($options['activeStatus'])) {
            $this->activeStatus = (string) $options['activeStatus'];
        }

        if (isset($options['useGroupDefaults'])) {
            $this->useGroupDefaults = (bool) $options['useGroupDefaults'];
        }
    }

    /**
     * @param UserPipelineData $pipelineData
     * @return UserPipelineData
     */
    public function build(UserPipelineData $pipelineData): UserPipelineData
    {
        if ($this->beforeBuild) {
            $data = ($this->beforeBuild)($pipelineData->data());
            $pipelineData = $pipelineData->change($data);
        }

        $data = [];
        foreach ($pipelineData->data() as $userData) {
            /** @var UserData $userData */
            if ($this->beforeUserBuild) {
                $userData = ($this->beforeUserBuild)($userData);
            }

            if (!$userData instanceof UserData) {
                continue;
            }

            $source = $pipelineData->sourceName($userData->key());
            $user = $this->buildUser($source, $userData);

            $data[] = $user;
        }

        if ($this->afterBuild) {
            $data = ($this->afterBuild)($data);
        }

        return $pipelineData->change($data);
    }

    /**
     * @param string $source
     * @param UserData $userData
     * @return UserData
     */
    protected function buildUser(string $source, UserData $userData): UserData
    {
        $index = $userData->key();
        $user = $userData->data();

        $email = (string)($this->emailBuilder ? $this->emailBuilder->call($this, $source, $index, $user) : $this->buildEmail($user));
        $phone = (string) ($this->phoneBuilder ? $this->phoneBuilder->call($this, $source, $index, $user) : $this->buildPhone($user));

        $data = [
            'login' => $this->loginBuilder
                ? $this->loginBuilder->call($this, $source, $index, $user)
                : $this->buildLogin($user),
            'first_name' => $this->firstNameBuilder
                ? $this->firstNameBuilder->call($this, $source, $index, $user)
                : $this->buildFirstName($user),
            'last_name' => $this->lastNameBuilder
                ? $this->lastNameBuilder->call($this, $source, $index, $user)
                : $this->buildLastName($user),
            'chief_email' => $this->chiefEmailBuilder
                ? $this->chiefEmailBuilder->call($this, $source, $index, $user)
                : $this->buildChiefEmail($user),
            'status' => $this->statusBuilder
                ? $this->statusBuilder->call($this, $source, $index, $user)
                : $this->buildStatus($user)
        ];

        if ($email && mb_strlen(trim($email)) !== 0) {
            $data['email'] = $email;
        }

        if ($phone && mb_strlen(trim($phone)) !== 0) {
            $data['phone'] = $phone;
        }

        $password = $this->passwordBuilder
            ? $this->passwordBuilder->call($this, $source, $index, $user)
            : $this->buildPassword($user);

        if($password) {
            $data['password'] = $password;
        }

        $data['groups'] = $this->buildGroups($source, $index, $user);

        if ($this->forms) {
            $forms = $this->forms;
            if ($this->buildForms) {
                $forms = $this->buildForms->call($this, $source, $index, $user, $forms);
            } else {
                foreach ($this->forms as $id => $form) {
                    $forms[(string) $id] = $user[$form] ?? self::DEFAULT_FORM_VALUE;
                }
            }

            $data['forms'] = $forms;
        }

        if($this->afterUserBuild) {
            $data = ($this->afterUserBuild)($source, $index, $user, $data);
        }

        return UserSyncData::fromData($index, $data);
    }

    /**
     * @param array $user
     * @return string|null
     */
    protected function buildLogin(array $user): ?string
    {
        $login = $user[$this->attributes['login']] ?? null;
        if ($login) {
            return trim($login);
        }

        return null;
    }

    /**
     * @param array $user
     * @return string|null
     */
    protected function buildFirstName(array $user): ?string
    {
        $firstName = $user[$this->attributes['first_name']] ?? null;
        if ($firstName) {
            return trim($firstName);
        }

        return null;
    }

    /**
     * @param array $user
     * @return string|null
     */
    protected function buildLastName(array $user): ?string
    {
        $lastName = $user[$this->attributes['last_name']] ?? null;
        if ($lastName) {
            return trim($lastName);
        }

        return null;
    }

    /**
     * @param array $user
     * @return string|null
     */
    protected function buildEmail(array $user): ?string
    {
        $email = $user[$this->attributes['email']] ?? null;
        if ($email) {
            return trim((string) $email);
        }

        return null;
    }

    /**
     * @param array $user
     * @return string|null
     */
    protected function buildPhone(array $user): ?string
    {
        $phone = $user[$this->attributes['phone']] ?? null;
        if (!$phone) {
            return null;
        }

        return PhoneBuilder::build($phone);
    }

    /**
     * @param string|null $email
     * @return bool
     */
    protected function buildVerifiedEmail(?string $email): bool
    {
        return $email ? true : false;
    }

    /**
     * @param string|null $phone
     * @return bool
     */
    protected function buildVerifiedPhone(?string $phone): bool
    {
        return $phone ? true : false;
    }

    /**
     * @param array $user
     * @return string|null
     */
    protected function buildChiefEmail(array $user): ?string
    {
        $chiefEmail = $user[$this->attributes['chief_email']] ?? null;
        if ($chiefEmail) {
            return trim($chiefEmail);
        }

        return null;
    }

    /**
     * @param array $user
     * @return string
     */
    protected function buildStatus(array $user): string
    {
        $status = $user[$this->attributes['status']] ?? null;
        return $status === $this->activeStatus ? 'active' : 'blocked';
    }

    /**
     * @param array $user
     * @return string|null
     */
    private function buildPassword(array $user): ?string
    {
        $password = $user[$this->attributes['password']] ?? null;
        if($password) {
            return trim($password);
        }

        return $password;
    }

    /**
     * @param string $source
     * @param string $index
     * @param array $user
     * @return array
     */
    protected function buildGroups(string $source, string $index, array $user): array
    {
        $groups = [];

        if($this->groupsBuilder) {
            return $this->groupsBuilder->call($this, $source, $index, $user);
        }

        if(!isset($this->attributes['groups']) || !is_array($this->attributes['groups'])) {
            return [];
        }

        foreach ($this->attributes['groups'] as $key => $group) {
            if(!isset($user[$group])) {
                continue;
            }

            $path = $this->trimValue($user[$group]);
            if(mb_strlen($path) > 0) {
                $groups[$key] = ['path' => $path];
            }
        }

        if($groups) {
            return $groups;
        }

        if($this->useGroupDefaults) {
            foreach ($this->groupDefaults as $key => $group) {
                if(is_string($group) && mb_strlen($group) > 0) {
                    $groups[$key] = ["path" => $this->trimValue($group)];
                }
            }
            return $groups;
        }

        return [];
    }

    protected function trimValue($group): string
    {
        if(!is_string($group)) {
            return '';
        }

        return trim(str_replace(' ', ' ', $group));
    }
}
