<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserFactory;

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
    /**
     * @var array
     */
    private $attributes = [
        'login' => 'USR_LOGIN',
        'first_name' => 'USR_FIRST_NAME',
        'last_name' => 'USR_LAST_NAME',
        'phone' => 'USR_MOBILE',
        'email' => 'USR_EMAIL',
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
     * @var callable
     */
    private $buildForms;

    /**
     * @var callable
     */
    private $beforeBuild;

    /**
     * @var callable
     */
    private $loginBuilder;
    /**
     * @var callable
     */
    private $firstNameBuilder;
    /**
     * @var callable
     */
    private $lastNameBuilder;

    /**
     * @var callable
     */
    private $emailBuilder;
    /**
     * @var callable
     */
    private $phoneBuilder;
    /**
     * @var callable
     */
    private $verifiedEmailBuilder;
    /**
     * @var callable
     */
    private $verifiedPhoneBuilder;
    /**
     * @var callable
     */
    private $chiefEmailBuilder;
    /**
     * @var callable
     */
    private $statusBuilder;
    /**
     * @var callable
     */
    private $groupRegionBuilder;
    /**
     * @var callable
     */
    private $groupCityBuilder;
    /**
     * @var callable
     */
    private $groupRoleBuilder;
    /**
     * @var callable
     */
    private $groupPositionBuilder;
    /**
     * @var callable
     */
    private $groupTeamBuilder;
    /**
     * @var callable
     */
    private $groupDepartmentBuilder;
    /**
     * @var callable
     */
    private $groupAssignmentBuilder;

    /**
     * @var string
     */
    private $activeStatus = self::USER_ACTIVE;

    /**
     * @var bool
     */
    private $useGroupDefaults = true;

    /**
     * TypicalUserFactory constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if(isset($options['attributes'])) {
            $this->attributes = array_merge($this->attributes, $options['attributes']);
        }

        if(isset($options['forms'])) {
            $this->forms = $options['forms'];
        }

        if(isset($options['beforeBuild']) && is_callable($options['beforeBuild'])) {
            $this->beforeBuild = $options['beforeBuild'];
        }

        if(isset($options['loginBuilder']) && is_callable($options['loginBuilder'])) {
            $this->loginBuilder = $options['loginBuilder'];
        }

        if(isset($options['firstNameBuilder']) && is_callable($options['firstNameBuilder'])) {
            $this->firstNameBuilder = $options['firstNameBuilder'];
        }

        if(isset($options['lastNameBuilder']) && is_callable($options['lastNameBuilder'])) {
            $this->lastNameBuilder = $options['lastNameBuilder'];
        }

        if(isset($options['emailBuilder']) && is_callable($options['emailBuilder'])) {
            $this->emailBuilder = $options['emailBuilder'];
        }

        if(isset($options['phoneBuilder']) && is_callable($options['phoneBuilder'])) {
            $this->phoneBuilder = $options['phoneBuilder'];
        }

        if(isset($options['verifiedEmailBuilder']) && is_callable($options['verifiedEmailBuilder'])) {
            $this->verifiedEmailBuilder = $options['verifiedEmailBuilder'];
        }

        if(isset($options['verifiedPhoneBuilder']) && is_callable($options['verifiedPhoneBuilder'])) {
            $this->verifiedPhoneBuilder = $options['verifiedPhoneBuilder'];
        }

        if(isset($options['chiefEmailBuilder']) && is_callable($options['chiefEmailBuilder'])) {
            $this->chiefEmailBuilder = $options['chiefEmailBuilder'];
        }

        if(isset($options['statusBuilder']) && is_callable($options['statusBuilder'])) {
            $this->statusBuilder = $options['statusBuilder'];
        }

        if(isset($options['groupRegionBuilder']) && is_callable($options['groupRegionBuilder'])) {
            $this->groupRegionBuilder = $options['groupRegionBuilder'];
        }

        if(isset($options['groupCityBuilder']) && is_callable($options['groupCityBuilder'])) {
            $this->groupCityBuilder = $options['groupCityBuilder'];
        }

        if(isset($options['groupRoleBuilder']) && is_callable($options['groupRoleBuilder'])) {
            $this->groupRoleBuilder = $options['groupRoleBuilder'];
        }

        if(isset($options['groupPositionBuilder']) && is_callable($options['groupPositionBuilder'])) {
            $this->groupPositionBuilder = $options['groupPositionBuilder'];
        }

        if(isset($options['groupTeamBuilder']) && is_callable($options['groupTeamBuilder'])) {
            $this->groupTeamBuilder = $options['groupTeamBuilder'];
        }

        if(isset($options['groupDepartmentBuilder']) && is_callable($options['groupDepartmentBuilder'])) {
            $this->groupDepartmentBuilder = $options['groupDepartmentBuilder'];
        }

        if(isset($options['groupAssignmentBuilder']) && is_callable($options['groupAssignmentBuilder'])) {
            $this->groupAssignmentBuilder = $options['groupAssignmentBuilder'];
        }

        if(isset($options['forms']) && is_array($options['forms'])) {
            $this->forms = $options['forms'];
        }

        if(isset($options['groupDefaults']) && is_array($options['groupDefaults'])) {
            $this->groupDefaults = array_merge($this->groupDefaults, $options['groupDefaults']);
        }

        if(isset($options['activeStatus'])) {
            $this->activeStatus = (string) $options['activeStatus'];
        }

        if(isset($options['useGroupDefaults'])) {
            $this->useGroupDefaults = (bool) $options['useGroupDefaults'];
        }
    }

    /**
     * @param UserPipelineData $pipelineData
     * @return UserPipelineData
     */
    public function build(UserPipelineData $pipelineData): UserPipelineData
    {
        $data = [];
        foreach ($pipelineData->data() as $userData) {
            if($this->beforeBuild) {
                $userData = ($this->beforeBuild)($userData);
            }

            if(!$userData instanceof UserData) {
                continue;
            }

            $data[] = $this->buildUser($userData);
        }

        return $pipelineData->change($data);
    }

    /**
     * @param UserData $userData
     * @return UserData
     */
    protected function buildUser(UserData $userData): UserData
    {
        $index = $userData->key();
        $user = $userData->data();

        $email = $this->emailBuilder ? ($this->emailBuilder)($index, $user) : $this->buildEmail($user);
        $phone = $this->phoneBuilder ? ($this->phoneBuilder)($index, $user) : $this->buildPhone($user);

        $data = [
            'login' => $this->loginBuilder
                ? ($this->loginBuilder)($index, $user)
                : $this->buildLogin($user),
            'first_name' => $this->firstNameBuilder
                ? ($this->firstNameBuilder)($index, $user)
                : $this->buildFirstName($user),
            'last_name' => $this->lastNameBuilder
                ? ($this->lastNameBuilder)($index, $user)
                : $this->buildLastName($user),
            'email' => $email,
            'phone' => $phone,
            'verified_email' => $this->verifiedEmailBuilder
                ? (bool)($this->verifiedEmailBuilder)($index, $user)
                : $this->buildVerifiedEmail($email),
            'verified_phone' => $this->verifiedPhoneBuilder
                ? (bool)($this->verifiedPhoneBuilder)($index, $user)
                : $this->buildVerifiedPhone($phone),
            'chief_email' => $this->chiefEmailBuilder
                ? ($this->chiefEmailBuilder)($index, $user)
                : $this->buildChiefEmail($user),
            'status' => $this->statusBuilder
                ? ($this->statusBuilder)($index, $user)
                : $this->buildStatus($user),
            'groups' => [
                'region' => $this->groupRegionBuilder
                    ? ($this->groupRegionBuilder)($index, $user)
                    : $this->buildGroup('groups.region', $user),
                'city' => $this->groupCityBuilder
                    ? ($this->groupCityBuilder)($index, $user)
                    : $this->buildGroup('groups.city', $user),
                'role' => $this->groupRoleBuilder
                    ? ($this->groupRoleBuilder)($index, $user)
                    : $this->buildGroup('groups.role', $user),
                'position' => $this->groupPositionBuilder
                    ? ($this->groupPositionBuilder)($index, $user)
                    : $this->buildGroup('groups.position', $user),
                'team' => $this->groupTeamBuilder
                    ? ($this->groupTeamBuilder)($index, $user)
                    : $this->buildGroup('groups.team', $user),
                'department' => $this->groupDepartmentBuilder
                    ? ($this->groupDepartmentBuilder)($index, $user)
                    : $this->buildGroup('groups.department', $user),
                'assignment' => $this->groupAssignmentBuilder
                    ? ($this->groupAssignmentBuilder)($index, $user)
                    : $this->buildGroup('groups.assignment', $user)
            ]
        ];

        if($this->forms) {
            $forms = $this->forms;
            if($this->buildForms) {
                $forms = ($this->buildForms)($index, $user, $forms);
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
        if($login) {
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
        if($firstName) {
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
        if($lastName) {
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
        if($email) {
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
        if(!$phone) {
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
        if($chiefEmail) {
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
     * @param string $type
     * @param array $user
     * @return null|string
     */
    protected function buildGroup(string $type, array $user): ?string
    {
        $group = $user[$this->attributes[$type]] ?? null;
        if($group) {
            return trim($group);
        }

        if($this->useGroupDefaults) {
            return $this->groupDefaults[$type];
        }

        return null;
    }
}