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
    protected const DEFAULT_REGION = 'Demo region';
    protected const DEFAULT_CITY = 'Demo city';
    protected const DEFAULT_ROLE = 'Demo role';
    protected const DEFAULT_POSITION = 'Demo position';
    protected const DEFAULT_TEAM = 'Demo team';
    protected const DEFAULT_DEPARTMENT = 'Demo department';
    protected const DEFAULT_ASSIGNMENT = 'Demo assignment';
    protected const DEFAULT_FORM_VALUE = null;
    protected const USER_ACTIVE = '0';
    protected const GROUPS = ['region', 'city', 'role', 'position', 'team', 'department', 'assignment'];
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
        'groups.region' => 'REGION_NAME',
        'groups.city' => 'CITY_NAME',
        'groups.role' => 'ROLE',
        'groups.position' => 'POSITION_NAME',
        'groups.team' => 'TEAM_NAME',
        'groups.department' => 'DEPARTAMENT_NAME',
        'groups.assignment' => 'ASSIGNMENT_NAME',
    ];

    private $groupDefaults = [
        'groups.region' => self::DEFAULT_REGION,
        'groups.city' => self::DEFAULT_CITY,
        'groups.role' => self::DEFAULT_ROLE,
        'groups.position' => self::DEFAULT_POSITION,
        'groups.team' => self::DEFAULT_TEAM,
        'groups.department' => self::DEFAULT_DEPARTMENT,
        'groups.assignment' => self::DEFAULT_ASSIGNMENT,
    ];

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
    private $verifiedEmailBuilder;
    /**
     * @var Closure
     */
    private $verifiedPhoneBuilder;
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
    private $groupRegionBuilder;
    /**
     * @var Closure
     */
    private $groupCityBuilder;
    /**
     * @var Closure
     */
    private $groupRoleBuilder;
    /**
     * @var Closure
     */
    private $groupPositionBuilder;
    /**
     * @var Closure
     */
    private $groupTeamBuilder;
    /**
     * @var Closure
     */
    private $groupDepartmentBuilder;
    /**
     * @var Closure
     */
    private $groupAssignmentBuilder;
    /**
     * @var Closure
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

        if (isset($options['verifiedEmailBuilder']) && is_callable($options['verifiedEmailBuilder'])) {
            $this->verifiedEmailBuilder = $options['verifiedEmailBuilder'];
        }

        if (isset($options['verifiedPhoneBuilder']) && is_callable($options['verifiedPhoneBuilder'])) {
            $this->verifiedPhoneBuilder = $options['verifiedPhoneBuilder'];
        }

        if (isset($options['chiefEmailBuilder']) && is_callable($options['chiefEmailBuilder'])) {
            $this->chiefEmailBuilder = $options['chiefEmailBuilder'];
        }

        if (isset($options['statusBuilder']) && is_callable($options['statusBuilder'])) {
            $this->statusBuilder = $options['statusBuilder'];
        }

        if (isset($options['groupRegionBuilder']) && is_callable($options['groupRegionBuilder'])) {
            $this->groupRegionBuilder = $options['groupRegionBuilder'];
        }

        if (isset($options['groupCityBuilder']) && is_callable($options['groupCityBuilder'])) {
            $this->groupCityBuilder = $options['groupCityBuilder'];
        }

        if (isset($options['groupRoleBuilder']) && is_callable($options['groupRoleBuilder'])) {
            $this->groupRoleBuilder = $options['groupRoleBuilder'];
        }

        if (isset($options['groupPositionBuilder']) && is_callable($options['groupPositionBuilder'])) {
            $this->groupPositionBuilder = $options['groupPositionBuilder'];
        }

        if (isset($options['groupTeamBuilder']) && is_callable($options['groupTeamBuilder'])) {
            $this->groupTeamBuilder = $options['groupTeamBuilder'];
        }

        if (isset($options['groupDepartmentBuilder']) && is_callable($options['groupDepartmentBuilder'])) {
            $this->groupDepartmentBuilder = $options['groupDepartmentBuilder'];
        }

        if (isset($options['groupAssignmentBuilder']) && is_callable($options['groupAssignmentBuilder'])) {
            $this->groupAssignmentBuilder = $options['groupAssignmentBuilder'];
        }

        if (isset($options['forms']) && is_array($options['forms'])) {
            $this->forms = $options['forms'];
        }

        if (isset($options['groupDefaults']) && is_array($options['groupDefaults'])) {
            $this->groupDefaults = array_merge($this->groupDefaults, $options['groupDefaults']);
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
            $data[] = $this->buildUser($source, $userData);
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

        $email = $this->emailBuilder ? $this->emailBuilder->call($this, $source, $index, $user) : $this->buildEmail($user);
        $phone = $this->phoneBuilder ? $this->phoneBuilder->call($this, $source, $index, $user) : $this->buildPhone($user);

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
            'email' => $email,
            'phone' => $phone,
            'verified_email' => $this->verifiedEmailBuilder
                ? (bool) $this->verifiedEmailBuilder->call($this, $source, $index, $user)
                : $this->buildVerifiedEmail($email),
            'verified_phone' => $this->verifiedPhoneBuilder
                ? (bool) $this->verifiedPhoneBuilder->call($this, $source, $index, $user)
                : $this->buildVerifiedPhone($phone),
            'chief_email' => $this->chiefEmailBuilder
                ? $this->chiefEmailBuilder->call($this, $source, $index, $user)
                : $this->buildChiefEmail($user),
            'status' => $this->statusBuilder
                ? $this->statusBuilder->call($this, $source, $index, $user)
                : $this->buildStatus($user)
        ];

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
            return trim($email);
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
        foreach (self::GROUPS as $group) {
            $groupBuilderMethod = 'group' . ucfirst($group) . 'Builder';
            $groupValue = $this->$groupBuilderMethod
                ? $this->$groupBuilderMethod->call($this, $source, $index, $user)
                : $this->buildGroup('groups.' . $group, $user);

            if(is_string($groupValue) && mb_strlen($groupValue) > 0) {
                $groups[$group] = $groupValue;
            }
        }

        return $groups;
    }

    /**
     * @param string $type
     * @param array $user
     * @return null|string
     */
    protected function buildGroup(string $type, array $user): ?string
    {
        $group = $user[$this->attributes[$type]] ?? null;
        if (is_null($group) || $group === '') {
            if ($this->useGroupDefaults) {
                return $this->groupDefaults[$type];
            }

            return null;
        }

        return trim(str_replace(' ', ' ', (string) $group));
    }
}
