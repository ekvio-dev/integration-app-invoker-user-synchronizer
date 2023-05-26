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

    protected function syncUsers(UserPipelineData $pipelineData, array $config = []): UserPipelineData
    {
        if ($pipelineData->data()) {
            $this->profiler->profile(sprintf('Synchronize %s users...', count($pipelineData->data())));

            $userDataKeyMap = [];
            $blockedUsers = [];
            $activeUsers = [];
            foreach ($pipelineData->data() as $index => $userData) {
                /** @var UserSyncData $userData */
                $userDataKeyMap[$index] = $userData->key();
                if($userData->data()['status'] === 'blocked') {
                    $blockedUsers[$index] = $userData->data();
                } else {
                    $activeUsers[$index] = $userData->data();
                }
            }

            $syncResultsBlocked = [];
            if (count($blockedUsers) > 0) {
                $this->profiler->profile(sprintf('Sync blocked %s users...', count($blockedUsers)));
                $syncResultsBlocked = $this->equeoUserApi->sync($blockedUsers, $config);
                $this->profiler->profile(sprintf('Get %s logs from Equeo...', count($syncResultsBlocked)));
            }

            $this->profiler->profile(sprintf('Sync active %s users...', count($activeUsers)));
            $syncResultsActive = $this->equeoUserApi->sync($activeUsers, $config);
            $this->profiler->profile(sprintf('Get %s logs from Equeo...', count($syncResultsActive)));


            $syncResults = array_merge($syncResultsBlocked, $syncResultsActive);

            foreach ($syncResults as $result) {
                if (isset($result['index']) && is_numeric($result['index'])) {

                    if(!isset($userDataKeyMap[$result['index']])) { //users without index (whitelist)
                        continue;
                    }

                    $result['index'] = $userDataKeyMap[$result['index']];
                }

                $pipelineData->addLog($result);
            }
        }

        return $pipelineData;
    }

    public function name(): string
    {
        return self::NAME;
    }
}
