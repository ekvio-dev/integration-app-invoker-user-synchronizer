<?php

declare(strict_types=1);

namespace Ekvio\Integration\Invoker;

use Ekvio\Integration\Contracts\Collector;
use Ekvio\Integration\Contracts\Invoker;
use Ekvio\Integration\Contracts\Profiler;
use Ekvio\Integration\Contracts\User\UserPipelineData;
use Ekvio\Integration\Invoker\UserFactory\UserFactory;
use Ekvio\Integration\Invoker\UserValidation\UserValidator;
use Ekvio\Integration\Sdk\V3\User\User;

/**
 * Class UserSynchronizer
 * @package App
 */
class UserSynchronizer implements Invoker
{
    protected const NAME = 'Synchronize users';
    /**
     * @var Collector
     */
    protected $userCollector;
    /**
     * @var UserFactory
     */
    protected $userFactory;
    /**
     * @var UserValidator
     */
    protected $validator;
    /**
     * @var User
     */
    protected $equeoUserApi;
    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * UserSynchronizer constructor.
     * @param Collector $userCollector
     * @param UserFactory $userFactory
     * @param UserValidator $validator
     * @param User $userSync
     * @param Profiler $profiler
     */
    public function __construct(
        Collector $userCollector,
        UserFactory $userFactory,
        UserValidator $validator,
        User $userSync,
        Profiler $profiler
    ) {
        $this->userCollector = $userCollector;
        $this->userFactory = $userFactory;
        $this->validator = $validator;
        $this->equeoUserApi = $userSync;
        $this->profiler = $profiler;
    }
    /**
     * @inheritDoc
     */
    public function __invoke(array $arguments = [])
    {
        $syncConfig = $arguments['parameters']['syncConfig'] ?? [];

        $this->profiler->profile('Begin collecting data...');
        /** @var UserPipelineData $userSyncPipelineData */
        $userSyncPipelineData = $this->userCollector->collect();
        $this->profiler->profile(sprintf('Collecting %s users...', count($userSyncPipelineData->data())));

        $this->profiler->profile('Building users from extracted data');
        $userSyncPipelineData = $this->userFactory->build($userSyncPipelineData);
        $this->profiler->profile(sprintf('Built %s users', count($userSyncPipelineData->data())));

        $this->profiler->profile(sprintf('Validating %s users....', count($userSyncPipelineData->data())));
        $userSyncPipelineData = $this->validator->validate($userSyncPipelineData);
        $this->profiler->profile(sprintf('Valid %s users...', count($userSyncPipelineData->data())));

        $userSyncPipelineData = $this->syncUsers($userSyncPipelineData, $syncConfig);
        $this->profiler->profile(sprintf('Summary %s logs...', count($userSyncPipelineData->logs())));

        return $userSyncPipelineData;
    }

    /**
     * @param UserPipelineData $pipelineData
     * @param array $config
     * @return UserPipelineData
     */
    protected function syncUsers(UserPipelineData $pipelineData, array $config = []): UserPipelineData
    {
        if ($pipelineData->data()) {
            $this->profiler->profile(sprintf('Synchronize %s users...', count($pipelineData->data())));

            $userDataKeyMap = [];
            $users = [];
            foreach ($pipelineData->data() as $index => $userData) {
                /** @var UserSyncData $userData */
                $userDataKeyMap[$index] = $userData->key();
                $users[] = $userData->data();
            }

            $syncResults = $this->equeoUserApi->sync($users, $config);
            $this->profiler->profile(sprintf('Get %s logs from Equeo...', count($syncResults)));

            foreach ($syncResults as $result) {
                if (isset($result['index']) && is_numeric($result['index'])) {
                    $result['index'] = $userDataKeyMap[$result['index']];
                }

                $pipelineData->addLog($result);
            }
        }

        return $pipelineData;
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return self::NAME;
    }
}
